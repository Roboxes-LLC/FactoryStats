google.charts.load('current', {packages: ['corechart', 'bar']});

class HourlyStatsChart
{
   constructor(container)
   {
      this.container = container;
      
      if (container != null)
      {
         this.chart = new google.visualization.ColumnChart(container);
         
         this.options = HourlyStatsChart.getOptions();
      }
   }

   setChartHours(startHour, endHour)
   {
      endHour = (endHour > startHour) ? endHour : 23;

      this.options.hAxis.viewWindow = {
         min: [startHour, 0, 0], 
         max: [endHour, 0, 0]
      };
   }
   
   setChartFontSize(titleFontSize, hAxisFontSize, annotationFontSize)
   {
      this.options.hAxis.titleTextStyle.fontSize = titleFontSize;
      this.options.hAxis.textStyle.fontSize = hAxisFontSize;
      this.options.annotations.textStyle.fontSize = annotationFontSize;
   }
   
   static getOptions()
   {
      var chartOptions = {
         legend: 'none',
         title: 'Screen Counts By Hour',
         hAxis: {
            title: 'Screen Counts By Hour',
            titleTextStyle:
            {
               color: 'white'
            },
            format: 'ha',
            viewWindow: {
               min: [5, 30, 0],  // 5:30am
               max: [16, 30, 0]  // 4:30pm
             },
             gridlines: {
                color: 'transparent'
             },
             textStyle: {
                color: 'white'
             },
         },
         vAxis: {
            textPosition: 'none',
            title: '',
            titleTextStyle:
            {
               color: 'white'
            },
            gridlines: {
               color: 'transparent'
            },
            textStyle: {
               color: 'white'
            },
         },
         annotations: {
            textStyle: {
               color: 'white'
            }
         },
         backgroundColor: '#000000',
      };
      
      return (chartOptions);
   }
   
   update(hourlyCounts)
   {
      if (this.chart && hourlyCounts)
      {
         var data = new google.visualization.DataTable();
         
         data.addColumn('timeofday', 'Time of Day');
         data.addColumn('number', 'Screen Count');
         data.addColumn({type: 'string', role: 'annotation'});  // bar annotation
         
         var rows = HourlyStatsChart.getRowsFromData(hourlyCounts);
         
         data.addRows(rows);
      
         this.chart.draw(data, this.options);
      }
   }
   
   static getRowsFromData(data)
   {
      var rows = [];
      
      for (var key in data)
      {
         var time = new Date(Date.parse(key));
   
         rows.push([{v: [time.getHours(), 0, 0]}, data[key], data[key].toString()]);
      }
      
      return (rows);
   }
}