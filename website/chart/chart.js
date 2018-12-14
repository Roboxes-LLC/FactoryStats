google.charts.load('current', {packages: ['corechart', 'bar']});
google.charts.setOnLoadCallback(setupChart);

var chart = null;
var dataTable = null;
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

function setupChart() {
   
   window.dataTable = new google.visualization.DataTable();
   
      window.chartOptions = {
        legend: 'none',
        title: 'Motivation Level Throughout the Day',
        hAxis: {
          title: 'Screen Counts By Hour',
          titleTextStyle:
          {
             color: 'white'
          },
          format: 'h:mm a',
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

      window.chart = new google.visualization.ColumnChart(
        document.getElementById('hourly-count-chart-div'));
    }

function drawChart(hourlyCounts)
{
   var data = new google.visualization.DataTable();
   
   data.addColumn('timeofday', 'Time of Day');
   data.addColumn('number', 'Motivation Level');
   
   // add annotation column role
   data.addColumn({type: 'string', role: 'annotation'});
   
   var rows = getRowsFromData(hourlyCounts);
   data.addRows(rows);

   window.chart.draw(data, window.chartOptions);
}