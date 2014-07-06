var device_id = 1;

// change this if you want to limit the amount of data pulled
var hours = 24 * 7;

var fullWidth = window.innerWidth * 0.97;
var fullHeight = window.innerHeight * 0.95;
var upperHeight = window.innerHeight * 0.85;
var lowerHeight = window.innerHeight * 0.10;

var margin = {top: 20, right: 70, bottom: 30 + lowerHeight, left: 50};
var margin2 = {top: 20 + upperHeight, right: 70, bottom: 30, left: 50};

var width = fullWidth - margin.left - margin.right;
var height = fullHeight - margin.top - margin.bottom;
var width2 = fullWidth - margin2.left - margin2.right;
var height2 = fullHeight - margin2.top - margin2.bottom;

var parseDate = d3.time.format("%Y-%m-%d %H:%M:%S").parse;

var x = d3.time.scale().range([0, width]);
var y = d3.scale.linear().range([height, 0]);
var yRight = d3.scale.linear().range([height, 0]);
var x2 = d3.time.scale().range([0, width]);
var y2 = d3.scale.linear().range([height2, 0]);
var yRight2 = d3.scale.linear().range([height2, 0]);

var color = d3.scale.category10();

// d3 x axis object for upper plot area
var xAxis = d3.svg.axis().
  scale(x)
  .orient("bottom")
  .ticks(width/80);

// d3 y axis object for upper plot area
var yAxis = d3.svg.axis()
  .scale(y)
  .orient("left");

// d3 y axis object for upper plot area
var yRightAxis = d3.svg.axis()
  .scale(yRight)
  .orient("right");

// d3 x axis object for lower plot area
var xAxis2 = d3.svg.axis()
  .scale(x2)
  .orient("bottom")
  .ticks(width/80);

// d3 brush object for lower plot area (panning/zooming)
var brush = d3.svg.brush()
  .x(x2)
  .on("brush", brushUpdate);

// d3 line object for upper trendlines (basis plot)
var line = d3.svg.line()
  .interpolate("basis")
  .x(function(d) { return x(d.date); })
  .y(function(d) { return y(d.val); });

// d3 line object for upper trendlines (basis plot)
var lineRight = d3.svg.line()
  .interpolate("basis")
  .x(function(d) { return x(d.date); })
  .y(function(d) { return yRight(d.val); });

// d3 line object for lower trendlines (basis plot)
var line2 = d3.svg.line()
  .interpolate("basis")
  .x(function(d) { return x2(d.date); })
  .y(function(d) { return y2(d.val); });

// d3 line object for lower trendlines (basis plot)
var lineRight2 = d3.svg.line()
  .interpolate("basis")
  .x(function(d) { return x2(d.date); })
  .y(function(d) { return yRight2(d.val); });
//
// d3 line object for upper trendlines (step function)
var lineStepafter = d3.svg.line()
  .interpolate("step-after")
  .x(function(d) { return x(d.date); })
  .y(function(d) { return y(d.val); });

// d3 line object for upper trendlines (step function)
var lineStepafter2 = d3.svg.line()
  .interpolate("step-after")
  .x(function(d) { return x2(d.date); })
  .y(function(d) { return y2(d.val); });

// create main svg object
var svg = d3.select("body").append("svg")
  .attr("width", fullWidth)
  .attr("height", fullHeight)

// create clip path so zoomed-in paths can't extend beyond the zoomed-out frame
svg.append("defs").append("clipPath")
  .attr("id", "clip")
  .append("rect")
  .attr("width", width)
  .attr("height", height);

// append upper <g>
var upper = svg.append("g")
  .attr("transform", "translate(" + margin.left + "," + margin.top + ")")

// append lower <g>
var lower = svg.append("g")
  .attr("transform", "translate(" + margin2.left + "," + margin2.top + ")")

// callback function for d3 brush object
function brushUpdate() {
  x.domain(brush.empty() ? x2.domain() : brush.extent());
  upper.select(".x.axis").call(xAxis);
  upper.selectAll(".plot path")
    .attr("d", function(d) { 
      if (d.name == "current") 
	return line(d.values); 
      else if (d.name == "humidity")
	return lineRight(d.values); 
      else
	return lineStepafter(d.values); 
    })
  upper.selectAll("circle")
    .attr("cx", function(d) { return x(d.date); }) 
    .attr("cy", function(d) { return y(d.val); }) 
}

function fetchData() {
// fetch the data
d3.json("fetch.php?id=" + device_id + "&hrs=" + hours, function(error, data) {
  color.domain(d3.keys(data[0]).filter(function(key) { return (key == "current" || key == "target" || key == "target2" || key == "humidity"); }));
  data.forEach(function(d) {
    d.date = parseDate(d.timestamp);
  });

  var points = color.domain().map(function(name) {
    var x = {
      name: name,
      values: data.map(function(d) {
	if(name == "target2")
          return { date: d.date, val: d[name] };
	  //if( d[name] == null) 
	  //else 
          //return { date: d.date, val: +d[name] };
	else
	  var xmode = "black";
	  if  (d["cooling"] == 1) { xmode = "blue";} else if (d["heating"] == 1) { xmode =  "red"; } 
          return { date: d.date, val: +d[name], mode: xmode };
      })
    };
    //console.log(x);
    return x;
  });

  // define the x-domains (i.e. min and max of actual date values)
  x.domain(d3.extent(data, function(d) { return d.date; }));
  x2.domain(x.domain());

  // define the y-domains (i.e. min and max of the union of all the trendlines)
  y.domain([
      +d3.min(points, function(c) { if (c.name == "target" || (c.name == "target2" && c.values != null) || c.name == "current") { return d3.min(c.values, function(v) { return v.val }); } else { return undefined; } }) - 0.5,
      +d3.max(points, function(c) { return d3.max(c.values, function(v) { return v.val }); }) + 0.5
  ]);
  yRight.domain([
      +d3.min(points, function(c) { if (c.name == "humidity") { return d3.min(c.values, function(v) { return v.val }); } else { return undefined; } }) - 0.5,
      +d3.max(points, function(c) { if (c.name == "humidity") { return d3.max(c.values, function(v) { return v.val }); } else { return undefined; } }) + 0.5 
  ]);
  y2.domain(y.domain());
  yRight2.domain(yRight.domain());

  // draw upper x axis
  upper.append("g")
    .attr("class", "x axis upper")
    .attr("transform", "translate(0," + height + ")")
    .call(xAxis);

  // draw upper y axis
  upper.append("g")
    .attr("class", "y axis upper")
    .call(yAxis)
    .append("text")
    .attr("transform", "rotate(-90)")
    .attr("y", 6)
    .attr("dy", ".71em")
    .style("text-anchor", "end")
    .text("Temperature (F)");
  
  upper.append("g")
    .attr("class", "y axis upper")
    .attr("transform", "translate(" + (width+15) + ",0)")
    .call(yRightAxis)
    .append("text")
    .attr("transform", "rotate(-90)")
    .attr("y", -12)
    .attr("dy", ".71em")
    .style("text-anchor", "end")
    .text("Humidity (%)");
  //
  // draw lower x axis
  lower.append("g")
    .attr("class", "x axis lower")
    .attr("transform", "translate(0," + height2 + ")")
    .call(xAxis2);

  // bind upper current/trendlines
  upper.selectAll(".plot.temps")
    .data(points.filter(function(f) { return (f.name == 'current' || f.name == 'humidity' || f.name == 'target' || (f.name == 'target2' && f.values != null)  ); }))
    .enter().append("g")
    .attr("class", function(d) { return "plot temps " + d.name; });

  // bind lower current and target trendlines
  lower.selectAll(".plot.temps")
    .data(points.filter(function(f) { return (f.name == 'current' || f.name == 'humidity' || f.name == 'target' || (f.name == 'target2' && f.values != null)); }))
    .enter().append("g")
    .attr("class", function(d) { return "plot temps " + d.name; });

  // draw upper current/target/furnace trendlines
  upper.selectAll(".plot")
    .append("path")
    .attr("class", "line")
    .attr("d", function(d) { 
      if (d.name == "current") 
	return line(d.values); 
      else if (d.name == "humidity")
	return lineRight(d.values); 
      else
	return lineStepafter(d.values); 
    })
    .style("stroke", function(d) { 
      if (d.name == 'target2')
      	return color('target'); 
      else 	
        return color(d.name); 
    })
    .attr("clip-path", "url(#clip)");

  // draw lower current/target trendlines
  lower.selectAll(".plot")
    .append("path")
    .attr("class", "line")
    .attr("d", function(d) { 
      if (d.name == "current") 
	return line2(d.values); 
      else if (d.name == "humidity")
	return lineRight2(d.values); 
      else
	return lineStepafter2(d.values); 
    })
    .style("stroke", function(d) { 
      if (d.name == 'target2')
      	return color('target'); 
      else 	
        return color(d.name); 
    });

  // create a parent element for the circles to live
  upper.selectAll(".current")
    .append("g")
    .attr("class", "circles")
    .attr("clip-path", "url(#clip)");

  // draw the circles with tooltips
  var format = d3.time.format("%a %b %-d %Y %-I:%M:%S %p");
  upper.selectAll(".circles").selectAll(".thecircles")
    .data((points.filter(function(f) { return f.name == 'current'; }))[0].values)
    .enter().append("circle")
    .attr("cx", function(d) { return x(d.date); }) 
    .attr("cy", function(d) { return y(d.val); }) 
    .attr("r", 5) 
    .attr("stroke", function(d) { return (d.mode); })
    .attr("fill", function(d) { return (d.mode); })
    .attr("opacity", 0.2) 
    .append("svg:title").text(function(d) {
      return format(d.date) + "\n" + d.val + "\u00B0 F";
    });
  
  // draw the d3 pan/zoom "brush" object
  lower.append("g")
    .attr("class", "x brush")
    .call(brush)
    .selectAll("rect")
    .attr("y", -6)
    .attr("height", height2 + 7);

  // draw legend	
  var legend = svg.append("g")
    .attr("class", "legend")
    .attr("x", 365)
    .attr("y", 325)
    .attr("height", 100)
    .attr("width", 100);
  
  legend.selectAll('g')
  .data(points.filter(function(f) { return f.name != 'target2'; }))
  .enter()
  .append('g')
  .each(function(d, i) {
     var g = d3.select(this);
     g.append("rect")
       .attr("x", width - 65)
       .attr("y", i*25)
       .attr("width", 10)
       .attr("height", 10)
       .style("fill", function(d) { return color(d.name); });
     
     g.append("text")
       .attr("x", width - 50)
       .attr("y", i * 25 + 8)
       .attr("height",30)
       .attr("width",100)
       .style("fill", function(d) { return color(d.name); })
       .text(d.name);
  
  });
});
}
function clearData() {
 lower.selectAll("rect").remove();
 lower.selectAll(".plot").remove();
 lower.selectAll(".x.axis").remove();
 lower.selectAll(".y.axis").remove();
 upper.selectAll(".plot").remove();
 upper.selectAll(".x.axis").remove();
 upper.selectAll(".y.axis").remove();
 upper.selectAll(".yRight.axis").remove();
}

  fetchData();
window.onload=function(){
  document.getElementById("device_id").onchange=
  function () {
        var aList = document.getElementById("device_id");
        window.device_id = aList.options[aList.selectedIndex].value;
	clearData();
	fetchData();
	if (!brush.empty()) {
	  brushUpdate();
	}
  }
  document.getElementById("hours").onkeypress=
  function (e) {
        var key=e.keyCode || e.which;
	if (key==13){
           window.hours = document.getElementById("hours").value;
	   clearData();
	   fetchData();
	   if (!brush.empty()) {
	     brushUpdate();
	   }
	}   
  }	
}
