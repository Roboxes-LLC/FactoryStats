class CycleTime
{
   // **************************************************************************
   //                                 Public
   
   constructor()
   {      
      window.onload = function() {
         this.onLoad();
      }.bind(this)
      
      this.chart = null;
      this.table = null;
      
      this.tolerance = null;
   }
   
   onLoad()
   {
      //this.createChart(CycleTime.PageElements.CYCLE_TIME_CHART_CONTAINER);
      
      this.createTable(CycleTime.PageElements.CYCLE_TIME_TABLE_CONTAINER, CycleTime.MAX_ROWS, CycleTime.MAX_COLS);
      
      document.getElementById(CycleTime.PageElements.STATION_INPUT).addEventListener('change', function(event) {
         this.update();
      }.bind(this));
      
      document.getElementById(CycleTime.PageElements.SHIFT_INPUT).addEventListener('change', function(event) {
         this.update();
      }.bind(this));
      
      document.getElementById(CycleTime.PageElements.MFG_DATE_INPUT).addEventListener('change', function(event) {
         this.update();
      }.bind(this));
      
      document.getElementById(CycleTime.PageElements.DOWNLOAD_LINK).addEventListener('click', function(event) {
         this.onDownloadData();
      }.bind(this));
      
      this.update();
      
      setInterval(function() {
         this.update();
      }.bind(this),
      5000);
   }
   
   setTolerance(good, fair, poor)
   {
      this.tolerance = {"good": good, "fair": fair, "poor": poor};
   }
   
   // **************************************************************************
   //                                 Private
 
    // HTML elements
   static PageElements = {
      // Container.
      "CYCLE_TIME_CHART_CONTAINER": "cycle-time-chart-container",
      "CYCLE_TIME_TABLE_CONTAINER": "cycle-time-table-container",
      // Inputs
      "MFG_DATE_INPUT" : "mfg-date-input",
      "SHIFT_INPUT" : "shift-input",
      "STATION_INPUT" : "station-input",
      // Links
      "DOWNLOAD_LINK" : "download-link"
   }
   
   static MAX_ROWS = 10;
   static MAX_COLS = 20;
   
   createChart(containerId)
   {
      let queryUrl = this.getChartQuery();
      
      let container = document.getElementById(containerId);

      //let visualization = new google.visualization.ColumnChart(container);
      let visualization = new google.visualization.LineChart(container);
            
      let options = 
      {
         size:'large',
         hAxis: {
            title: 'Time',
            format: 'HH:MM'
         },
         vAxis: {
            title: 'Cycle Time (s)',
         },
         bar: {
            groupWidth: 25
         },
         interpolateNulls: false,
      }
   
      this.chart = new Chart(queryUrl, visualization, options, container);
   
      this.chart.sendAndDraw();
   }
   
   getChartQuery()
   {
      let stationId = parseInt(document.getElementById(CycleTime.PageElements.STATION_INPUT).value);
      let shiftId = parseInt(document.getElementById(CycleTime.PageElements.SHIFT_INPUT).value);
      let mfgDate = document.getElementById(CycleTime.PageElements.MFG_DATE_INPUT).value;
      
      let queryUrl = `/app/page/cycleTime/?request=fetch_cycle_times&stationId=${stationId}&shiftId=${shiftId}&date=${mfgDate}`;

      return (queryUrl);
   }
   
   createTable(containerId, rows, columns)
   {
      let container = document.getElementById(containerId);
      
      this.table = document.createElement("table");
      container.appendChild(this.table);
      
      let firstCell = true;
      
      for (let row = 0; row < rows; row++)
      {
         let row = this.table.insertRow(-1);
         
         for (let col = 0; col < columns; col++)
         {
            let cell = row.insertCell(-1);
            cell.classList.add("cycle-time-cell");
            
            if (firstCell)
            {
               cell.classList.add("first");
               firstCell = false;
            }
         }
      }
   }
   
   update()
   {
      ajaxRequest(this.getChartQuery(), function(response) {
         if (response.success)
         {
            //this.chart.handleResponse(response);
            this.populateTable(response.rows);
         }
         else
         {
            console.log(response.error);
         }
      }.bind(this));
   }
   
   populateTable(data)
   {
      let rows = this.table.rows.length;
      let cols = this.table.rows[0].cells.length;
      
      // Clear table.
      for (let rowIndex = 0; rowIndex < CycleTime.MAX_ROWS; rowIndex++)
      {
         for (let colIndex = 0; colIndex < CycleTime.MAX_COLS; colIndex++)
         {
            let cell = this.table.rows[rowIndex].cells[colIndex];
            
            cell.innerHTML = null;
            
            cell.classList.remove("tolerance-good");
            cell.classList.remove("tolerance-fair");
            cell.classList.remove("tolerance-poor");
            cell.classList.remove("tolerance-bad");
            cell.classList.remove("unknown");
         }  
      }
      
      // Populate table.
      let i = 0;
      for (let time of Object.keys(data).reverse())
      {
         let cycleTime = data[time];
         
         let colIndex = Math.floor(i / rows);
         let rowIndex = (i % rows);
       
         if ((rowIndex < rows) && (colIndex < cols))
         {
            let cell = this.table.rows[rowIndex].cells[colIndex];
            
            cell.innerHTML = cycleTime;
            
            if (cycleTime === null)
            {
               cell.classList.add("unknown");
            }

            if (this.tolerance)
            {
               if (cycleTime <= this.tolerance.good)
               {
                  cell.classList.add("tolerance-good");
               }
               else if (cycleTime <= this.tolerance.fair)
               {
                  cell.classList.add("tolerance-fair");
               }
               else if (cycleTime <= this.tolerance.poor)
               {
                  cell.classList.add("tolerance-poor");
               }
               else
               {
                  cell.classList.add("tolerance-bad");
               }
            }

            i++;
         }
         else
         {
            break;
         }
      }
   }
   
   onDownloadData()
   {
      let stationId = parseInt(document.getElementById(CycleTime.PageElements.STATION_INPUT).value);
      let shiftId = parseInt(document.getElementById(CycleTime.PageElements.SHIFT_INPUT).value);
      let mfgDate = document.getElementById(CycleTime.PageElements.MFG_DATE_INPUT).value;
      
      let url = `/app/page/cycleTime/?request=download_csv&stationId=${stationId}&shiftId=${shiftId}&date=${mfgDate}`;
      console.log(url);
      
      ajaxRequest(url, function(response) {
         if (response.success)
         {
            // Trigger download.
            const link = document.createElement('a');
            link.href = response.url;
            link.setAttribute('download', response.filename);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
         }
         else
         {
            console.log(response.error);
         }
      }.bind(this));
   }
}