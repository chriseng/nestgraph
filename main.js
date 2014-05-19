
 nestGraph = {
    'init': function() {
            device_id = 1;

            // change this if you want to limit the amount of data pulled
            hours = 24 * 7;
            
            var graph_info = {
                fullWidth : window.innerWidth * 0.97,
                
                fullHeight : window.innerHeight * 0.95,
                
                plot_info_arr : [
                   {
                      name : "Energy",
                      height : window.innerWidth * 0.80 * .25,
                      width : window.innerWidth * 0.80,
                      margin : {top: 60, right: 60, bottom: 0, left: 50},
                      hasRightAxis : false
                      },
                      
                   {
                      name : "Energy Brush",
                      height : 100,
                      width : window.innerWidth * 0.80,
                      margin : {top: 60, right: 60, bottom: 0, left: 50},
                      hasRightAxis : false
                      },
                      
                   {
                      name : "Cycles",
                      height : 40,
                      width : window.innerWidth * 0.80,
                      margin : {top: 60, right: 60, bottom: 0, left: 50},
                      hasRightAxis : false
                      },
                      
                   {
                      name : "Log Data",
                      height : window.innerWidth * 0.80 * .5,
                      width : window.innerWidth * 0.80,
                      margin : {top: 60, right: 60, bottom: 0, left: 50},
                      hasRightAxis : true
                      },
                      
                   {
                      name : "Events",
                      height : 400,
                      width : window.innerWidth * 0.80,
                      margin : {top: 60, right: 60, bottom: 0, left: 50},
                      hasRightAxis : false
                      },
                   ],
                   
                   set_x_y_scale : function() {
                      var i;
                      for( i = 0; i < this.plot_info_arr.length; i += 1) {
                        var this_plot = this.plot_info_arr[i];
                        var x = d3.time.scale().range([0, this_plot.width]);
                        var y = d3.scale.linear().range([this_plot.height, 0]);
                        
                        this_plot.x = x;
                        this_plot.y = y;
                        this_plot.y2 = y2;
                        this_plot.xAxis = d3.svg.axis().
                          scale(x)
                          .orient("bottom")
                          .ticks(this_plot.width/80);
                          
                         this_plot.yAxis = d3.svg.axis()
                          .scale(y)
                          .orient("left");
                        if(this_plot.hasRightAxis === true) {
                          var y2 = d3.scale.linear().range([this_plot.height, 0]);
                          this_plot.y2 = y2;
                          this_plot.yRightAxis = d3.svg.axis()
                            .scale(y2)
                            .orient("right");
                        }
                      }
                      this.calc_height();
                   },
                   
                   calc_height : function() {
                      var total_height = 0;
                      for( i = 0; i < this.plot_info_arr.length; i += 1) {
                        this.plot_info_arr[i].height_offset = total_height;
                        total_height += this.plot_info_arr[i].margin.top + this.plot_info_arr[i].height;
                        
                      }
                      this.fullHeight = total_height + 50; //Leave a margin of on the bottom
                   },
                   
                   append_plots : function(svg) {
                      var i;
                      var total_offset = 0;
                      for( i = 0; i < this.plot_info_arr.length; i += 1) {
                        var this_plot = this.plot_info_arr[i];
                        total_offset += this_plot.margin.top;
                        
                        this_plot.svg_plot = svg.append("g")
                          .attr("transform", "translate(" + this_plot.margin.left + "," + total_offset + ")");
                         total_offset += this_plot.height;
                      }
                   },
                   
                   clearData : function() {
                      var i;
                      var total_offset = 0;
                      for( i = 0; i < this.plot_info_arr.length; i += 1) {
                        var this_plot = this.plot_info_arr[i];
                        this_plot.svg_plot.selectAll("rect").remove();
                        this_plot.svg_plot.selectAll(".plot").remove();
                        this_plot.svg_plot.selectAll(".x.axis").remove();
                        this_plot.svg_plot.selectAll(".y.axis").remove();
                      }
                   }
                   
            };

          graph_info.set_x_y_scale();
          
          console.log("Length: " + graph_info.plot_info_arr.length);
          parseDate = d3.time.format("%Y-%m-%d %H:%M:%S").parse;

          color = d3.scale.category10();

          // d3 brush object for lower plot area (panning/zooming)
           brush = d3.svg.brush()
            .x(graph_info.plot_info_arr[1].x)
            .on("brush", brushUpdate);

          // create main svg object
           svg = d3.select("body").append("svg")
            .attr("width", graph_info.fullWidth)
            .attr("height", graph_info.fullHeight)

          // create clip path so zoomed-in paths can't extend beyond the zoomed-out frame
          svg.append("defs").append("clipPath")
            .attr("id", "clip")
            .append("rect")
            .attr("width", graph_info.plot_info_arr[0].width)
            .attr("height", graph_info.plot_info_arr[0].height);

          graph_info.append_plots(svg);
          

          // callback function for d3 brush object
          function brushUpdate() {
            brush_main_plot = graph_info.plot_info_arr[0];
            brush_small_plot = graph_info.plot_info_arr[1];
            brush_main_plot.x.domain(brush.empty() ? brush_small_plot.x.domain() : brush.extent());
            
            console.log(brush.extent());
            brush_domain = brush.extent();
            brush_domain_time = [];
            var short_date_format = d3.time.format("%Y-%m-%d");
            brush_domain_time[0] = short_date_format(brush_domain[0]);
            brush_domain_time[1] = short_date_format(brush_domain[1]);
            //console.log(brush_domain_time);
            fetchData(brush_domain_time);
            
            /*
            brush_main_plot.svg_plot.select(".x.axis").call(brush_main_plot.xAxis);
            brush_main_plot.x.domain(brush_domain);
            
            //brush_main_plot.svg_plot.focus.select(".x.axis").call(xAxis);
            
            brush_main_plot.rect.selectAll("rect")
              .data(function(d) { return d.values; } )
              .enter()
              .append("rect")
              .attr("x", function(d) { return brush_main_plot.xAxis(d.date); })
              .attr("y", function(d) { return brush_main_plot.y(d.val); })
              .attr("width", function(d) { return (brush_main_plot.width / data.length) - 1; }) // - 1 for paeeing
              .attr("height", function(d) { return brush_main_plot.height - brush_main_plot.y(d.val); })
              .attr("fill", function(d) { return d.color; })
              .attr("fill-opacity", .5);
             */
            /*
            brush_main_plot.svg_plot.selectAll("rect")
                .attr("transform", "translate(" + d3.event.translate[0] + 
                      ",0)scale(" + d3.event.scale + ", 1)");
            */
            /*
            brush_main_plot.svg_plot.selectAll(".plot path")
              .attr("d", function(d) { 
                return brush_main_plot.line(d.values); 
              })
              
              */
          }
          
          function fetchEnergy() {
          // fetch the data
          d3.json("fetch.php?id=" + device_id + "&hrs=" + hours + "&data=energy", function(error, data) {
            this_plot = graph_info.plot_info_arr[0];
            this_brush_plot = graph_info.plot_info_arr[1];
            //console.log(data);
            
            color.domain(d3.keys(data[0]).filter(function(key) { return (key == 'heating' || key == 'cooling' || 
                      key == 'fan' || key == 'humid' || key == 'dehumid' ); }));
            data.forEach(function(d) {
              d.date = parseDate(d.timestamp);
            });

             points = color.domain().map(function(name) {
               x = {
                name: name,
                values: data.map(function(d) {
                      xcolor = "black";
                      if  (name == "cooling") {
                        xcolor = "blue";
                      } 
                      else if (name == "heating") {
                        xcolor =  "red"; 
                      }
                      else if (name == "fan") {
                        xcolor =  "yellow"; 
                      } 
                      else if (name == "humid") {
                        xcolor =  "green"; 
                      }
                      else if (name == "dehumid") {
                        xcolor =  "cyan"; 
                      }
                      
                      return { date: d.date, 
                          val: +d[name], 
                          color: xcolor 
                          };
                    })
                 };
              //console.log(x);
              return x;
            });
            
            // define the x-domains (i.e. min and max of actual date values)
            var date_domain = d3.extent(data, function(d) { return d.date; });
            console.log (date_domain);
            date_domain[1] = new Date(+date_domain[1] + 86400000); //add an extra day
            this_plot.x.domain(date_domain);
            this_brush_plot.x.domain(date_domain);
            
            // define the y-domains (i.e. min and max of the union of all the trendlines)
            var y_domain = [0,
                +d3.max(points, function(c) { return d3.max(c.values, function(v) { return v.val }); }) * 1.1];
            this_plot.y.domain(y_domain);
            this_brush_plot.y.domain(y_domain);

            // draw nest_data_plot x axis
            this_plot.svg_plot.append("g")
              .attr("class", "x axis nest_data_plot")
              .attr("transform", "translate(0," + this_plot.height + ")")
              .call(this_plot.xAxis);

            // draw nest_data_plot y axis
            this_plot.svg_plot.append("g")
              .attr("class", "y axis nest_data_plot")
              .call(this_plot.yAxis)
              .append("text")
              .attr("transform", "rotate(-90)")
              .attr("y", 6)
              .attr("dy", ".71em")
              .style("text-anchor", "end")
              .text("Duration (Sec)");
            
            // draw the brush plot x-axis
            this_brush_plot.svg_plot.append("g")
              .attr("class", "x axis brush")
              .attr("transform", "translate(0," + this_brush_plot.height + ")")
              .call(this_brush_plot.xAxis);
              
            // draw the brush plot y-axis
            this_brush_plot.svg_plot.append("g")
              .attr("class", "y axis brush")
              .call(this_brush_plot.yAxis)
              .append("text")
              .attr("transform", "rotate(-90)")
              .attr("y", 6)
              .attr("dy", ".71em")
              .style("text-anchor", "end")
              .text("Duration (Sec)");
              
            // bind the energy data current/trendlines
            this_plot.rect = this_plot.svg_plot.selectAll(".plot.energies")
              .data(points.filter(function(f) {return (f.name == 'heating' || f.name == 'cooling' || 
                      f.name == 'fan' || f.name == 'humid' || f.name == 'dehumid' ); }))
              .enter()
              .append("g")
              .attr("class", function(d) { return "plot duration " + d.name; });
              
            this_plot.rect.selectAll("rect")
              .data(function(d) { console.log (d); return d.values; } )
              .enter()
              .append("rect")
              .attr("x", function(d) { return this_plot.x(d.date); })
              .attr("y", function(d) { return this_plot.y(d.val); })
              .attr("width", function(d) { return (this_plot.width / data.length) - 1; }) // - 1 for paeeing
              .attr("height", function(d) { return this_plot.height - this_plot.y(d.val); })
              .attr("fill", function(d) { return d.color; })
              .attr("fill-opacity", .5);
            
            // bind data to the brush plot
            this_brush_plot.svg_plot.selectAll(".plot.energies")
              .data(points.filter(function(f) {return (f.name == 'heating' || f.name == 'cooling' ); }))
              .enter()
              .append("g")
              .attr("class", function(d) { return "plot brush duration " + d.name; })
              .selectAll("rect")
              .data(function(d) { console.log (d); return d.values; } )
              .enter()
              .append("rect")
              .attr("x", function(d) { return this_brush_plot.x(d.date); })
              .attr("y", function(d) { return this_brush_plot.y(d.val); })
              .attr("width", function(d) { return (this_brush_plot.width / data.length) - 1; }) // - 1 for paeeing
              .attr("height", function(d) { return this_brush_plot.height - this_brush_plot.y(d.val); })
              .attr("fill", function(d) { return d.color; })
              .attr("fill-opacity", .5);
            
            // draw the d3 pan/zoom "brush" object
            this_brush_plot.svg_plot.append("g")
              .attr("class", "x brush")
              .call(brush)
              .selectAll("rect")
              .attr("y", -6)
              .attr("height", this_brush_plot.height + 7);
              
          } //End fetchEnergy()
          )};

          function fetchData (timeRange) {
          var fetch_string;
          if(typeof(timeRange) !== "undefined") {
            fetch_string = "id=" + device_id + "&start=\"" + timeRange[0] + "\"&end=\"" + timeRange[1] + "\"";
          }
          else {
            fetch_string = "id=" + device_id + "&hrs=" + hours;
          }
          // fetch the data
          //console.log(fetch_string);
          d3.json("fetch.php?" + fetch_string, function(error, data) {
            this_plot = graph_info.plot_info_arr[3];
            events_plot = graph_info.plot_info_arr[4];
            
                        
            this_plot.svg_plot.selectAll(".plot").remove();
            this_plot.svg_plot.selectAll(".x.axis").remove();
            this_plot.svg_plot.selectAll(".y.axis").remove();
            svg.selectAll(".legend").remove();
            
            events_plot.svg_plot.selectAll(".plot").remove();
            events_plot.svg_plot.selectAll(".x.axis").remove();
            events_plot.svg_plot.selectAll(".y.axis").remove();
            
            color.domain(d3.keys(data[0]).filter(function(key) { return (key == "current" || key == "target" //|| key == "target2" 
                || key == "humidity" || key == "outside" 
                || key == 'heating' || key == 'cooling' || key == 'fan' || key == 'autoAway' 
                || key == 'manualAway' || key == 'leaf'); }));
            data.forEach(function(d) {
              d.date = parseDate(d.timestamp);
            });

             points = color.domain().map(function(name) {
               x = {
                name: name,
                values: data.map(function(d) {
                    var xcolor = "black";
                    var xval = +d[name];
                    switch(name) {
                      case "heating": 
                          xcolor = "red";
                          xval += 12;
                          break;
                      case "cooling" :
                          xcolor = "darkblue";
                          xval += 10;
                          break;
                      case "fan" :
                          xcolor = "violet";
                          xval += 8;
                          break;
                      case "autoAway" :
                          xcolor = "skyblue";
                          xval += 6;
                          break;
                      case "manualAway" :
                          xcolor = "steelblue";
                          xval += 4;
                          break;
                      case "leaf" :
                          xcolor = "darkgreen";
                          xval += 2;
                          break;
                      case "target2":
                      case "target" :
                          xcolor = "HotPink";
                          break;
                      case "current" :
                          xcolor = "black";
                          break;
                      case "humidity":
                          xcolor = "green";
                          break;
                      case "outside":
                          xcolor = "blue";
                          break;
                      default:
                          break;
                     }
                    if(name == "target2") {
                      return { date: d.date, val: d[name] };
                    }
                    //if( d[name] == null) 
                    //else 
                          //return { date: d.date, val: +d[name] };
                    else
                       xmode = "black";
                      if  (d["cooling"] == 1) {
                        xmode = "blue";
                      } 
                      else if (d["heating"] == 1) {
                        xmode =  "red"; 
                      } 
                      
                      return { date: d.date, 
                          val: xval, 
                          mode: xmode,
                          color: xcolor
                          };
                    })
                 };
              //console.log(x);
              return x;
            });

            
            // define the x-domains (i.e. min and max of actual date values)
            var x_domain = d3.extent(data, function(d) { return d.date; });
            this_plot.x.domain(x_domain);
            
            // define the y-domains (i.e. min and max of the union of all the trendlines)
            this_plot.y.domain([
                +d3.min(points, function(c) { if (c.name == "target" //|| (c.name == "target2" && c.values != null) 
                || c.name == "current" || c.name == "outside") { return d3.min(c.values, function(v) { return v.val }); } else { return undefined; } }) - 1,
                +d3.max(points, function(c) { return d3.max(c.values, function(v) { return v.val }); }) + 1
            ]);
            this_plot.y2.domain([
                +d3.min(points, function(c) { if (c.name == "humidity") { return d3.min(c.values, function(v) { return v.val }); } else { return undefined; } }) - 1,
                +d3.max(points, function(c) { if (c.name == "humidity") { return d3.max(c.values, function(v) { return v.val }); } else { return undefined; } }) + 1 
            ]);
            
            //Setup the events plot axis
            events_plot.x.domain(x_domain);
            events_plot.y.domain([0, 14]); // Fixed number of events all 0/1

            // draw nest_data_plot x axis
            this_plot.svg_plot.append("g")
              .attr("class", "x axis nest_data_plot")
              .attr("transform", "translate(0," + this_plot.height + ")")
              .call(this_plot.xAxis);

            // draw nest_data_plot y axis
            this_plot.svg_plot.append("g")
              .attr("class", "y axis nest_data_plot")
              .call(this_plot.yAxis)
              .append("text")
              .attr("transform", "rotate(-90)")
              .attr("y", 6)
              .attr("dy", ".71em")
              .style("text-anchor", "end")
              .text("Temperature (F)");
            
            this_plot.svg_plot.append("g")
              .attr("class", "y axis nest_data_plot")
              .attr("transform", "translate(" + (this_plot.width+15) + ",0)")
              .call(this_plot.yRightAxis)
              .append("text")
              .attr("transform", "rotate(-90)")
              .attr("y", -12)
              .attr("dy", ".71em")
              .style("text-anchor", "end")
              .text("Humidity (%)");
              
            // draw events x axis
            events_plot.svg_plot.append("g")
              .attr("class", "x axis events")
              .attr("transform", "translate(0," + events_plot.height + ")")
              .call(events_plot.xAxis);

            // draw events y axis
            events_plot.svg_plot.append("g")
              .attr("class", "y axis events")
              .call(events_plot.yAxis)
              .append("text")
              .attr("transform", "rotate(-90)")
              .attr("y", 6)
              .attr("dy", ".71em")
              .style("text-anchor", "end")
              .text("Event");
            
            // bind nest_data_plot current/trendlines
            this_plot.svg_plot.selectAll(".plot.temps")
              .data(points.filter(function(f) { return (f.name == 'current' || f.name == 'outside' || f.name == 'humidity' || f.name == 'target' || (f.name == 'target2' && f.values != null)  ); }))
              .enter().append("g")
              .attr("class", function(d) { return "plot temps " + d.name; });
              
              
            // bind date for events
            events_plot.svg_plot.selectAll(".plot.temps")
              .data(points.filter(function(f) { return (f.name == 'heating' || f.name == 'cooling' || f.name == 'fan' || f.name == 'autoAway' || 
                          f.name == 'manualAway' || f.name == 'leaf'); }))
              .enter().append("g")
              .attr("class", function(d) { return "plot temps " + d.name; });


            
            //Create the line objects once
            if (typeof this_plot.line === 'undefined') {
              this_plot.line = d3.svg.line()
                .interpolate("basis")
                .x(function(d) { return this_plot.x(d.date); })
                .y(function(d) { return this_plot.y(d.val); });

              this_plot.lineStepafter = d3.svg.line()
                .interpolate("step-after")
                .x(function(d) { return this_plot.x(d.date); })
                .y(function(d) { return this_plot.y(d.val); });
                
              if(this_plot.hasRightAxis === true) {
                this_plot.lineRight = d3.svg.line()
                  .interpolate("basis")
                  .x(function(d) { return this_plot.x(d.date); })
                  .y(function(d) { return this_plot.y2(d.val); });
              }
            }
            
            //All events are step after (logical 0/1)
            if (typeof events_plot.lineStepafter === 'undefined') {
              events_plot.lineStepafter = d3.svg.line()
                .interpolate("step-after")
                .x(function(d) { return events_plot.x(d.date); })
                .y(function(d) { return events_plot.y(d.val); });
            }

            // draw nest_data_plot current/target/furnace trendlines
            this_plot.svg_plot.selectAll(".plot")
              .append("path")
              .attr("class", "line")
              .attr("d", function(d) { 
                if (d.name == "current" || d.name == "outside") 
                    return this_plot.line(d.values); 
                else if (d.name == "humidity")
                    return this_plot.lineRight(d.values); 
                else
                    return this_plot.lineStepafter(d.values); 
              })
              .style("stroke", function(d) { 
                  return d.values[0].color; 
              });
              //.attr("clip-path", "url(#clip)");
              
            // draw events plots
            events_plot.svg_plot.selectAll(".plot")
              .append("path")
              .attr("class", "line")
              .attr("d", function(d) { 
                    return events_plot.lineStepafter(d.values); 
              })
              .style("stroke", function(d) {  
                  return d.values[0].color; 
              });

            // create a parent element for the circles to live
            this_plot.svg_plot.selectAll(".current")
              .append("g")
              .attr("class", "circles")
              .attr("clip-path", "url(#clip)");

            // draw the circles with tooltips
             format = d3.time.format("%a %b %-d %Y %-I:%M:%S %p");
            this_plot.svg_plot.selectAll(".circles").selectAll(".thecircles")
              .data((points.filter(function(f) { return f.name == 'current'; }))[0].values)
              .enter().append("circle")
              .attr("cx", function(d) { return this_plot.x(d.date); }) 
              .attr("cy", function(d) { return this_plot.y(d.val); }) 
              .attr("r", 5) 
              .attr("stroke", function(d) { return (d.mode); })
              .attr("fill", function(d) { return (d.mode); })
              .attr("opacity", 0.2) 
              .append("svg:title").text(function(d) {
                return format(d.date) + "\n" + d.val + "\u00B0 F";
              });
            
            // draw legend  
             legend = svg.append("g")
              .attr("class", "legend")
              .attr("x", 365)
              .attr("y", this_plot.height_offset + this_plot.height)
              .attr("height", 100)
              .attr("width", 100);
            
            legend.selectAll('g')
            .data(points.filter(function(f) { return f.name != 'target2'; }))
            .enter()
            .append('g')
            .each(function(d, i) {
                g = d3.select(this);
               g.append("rect")
                 .attr("x", this_plot.width + 90)
                 .attr("y", this_plot.height_offset + this_plot.height + i*25)
                 .attr("width", 10)
                 .attr("height", 10)
                 .style("fill", function(d) { return d.values[0].color; });
               
               g.append("text")
                 .attr("x", this_plot.width + 105)
                 .attr("y", this_plot.height_offset + this_plot.height + i * 25 + 8)
                 .attr("height",30)
                 .attr("width",100)
                 .style("fill", function(d) { return d.values[0].color; })
                 .text(d.name);
            
            });
          });
          };

          fetchEnergy();
          fetchData();
          
            
          window.onload=function(){
            document.getElementById("device_id").onchange=
            function () {
                   aList = document.getElementById("device_id");
                  window.device_id = aList.options[aList.selectedIndex].value;
            graph_info.clearData();
            fetchEnergy();
            fetchData();
            if (!brush.empty()) {
              brushUpdate();
            }
            }
          };
    }
};

nestGraph.init();
//document.write("Device ID: " + nestGraph.device_id);
