google.charts.load('current', {packages: ['corechart', 'bar']});
google.charts.setOnLoadCallback(setupChart);

var hourlyCountChart = null;
var chartOptions = null;

function getRowsFromData(data)
{
   var rows = [];
   
   for (key in data)
   {
      var time = new Date(Date.parse(key));

      rows.push([{v: [time.getHours(), 0, 0]}, data[key], data[key].toString()]);
   }
   
   return (rows);
}

function setupChart()
{
   chartOptions = {
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
            min: [7, 30, 0],
            max: [17, 30, 0]
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
      backgroundColor: '#000000',
   };

   hourlyCountChart = new google.visualization.ColumnChart(document.getElementById('hourly-count-chart-div'));
}

function drawChart(hourlyCounts)
{
   if (hourlyCountChart && hourlyCounts)
   {
      var data = new google.visualization.DataTable();
      
      data.addColumn('timeofday', 'Time of Day');
      data.addColumn('number', 'Screen Count');
      data.addColumn({type: 'string', role: 'annotation'});  // bar annotation
      
      var rows = getRowsFromData(hourlyCounts);
      
      data.addRows(rows);
   
      hourlyCountChart.draw(data, chartOptions);
   }
}