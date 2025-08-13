google.charts.load('current', {packages: ['corechart', 'bar']});

class Chart
{
   constructor(query, visualization, visOptions, errorContainer)
   {
      this.query = query;
      this.visualization = visualization;
      this.options = visOptions || {};
      this.errorContainer = errorContainer;
      this.currentDataTable = null;

      if (!visualization || 
          !('draw' in visualization) ||
          (typeof(visualization['draw']) != 'function'))
      {
         throw Error('Visualization must have a draw method.');
      }
   }
  
   draw()
   {
      if (!this.currentDataTable)
      {
         return;
      }
      
      this.visualization.draw(this.currentDataTable, this.options);
   }

   /*   
   sendAndDraw()
   {
      var query = this.query;
      var self = this;
      
      query.send(function(response) {
         self.handleResponse(response)
      });
   }
   */
   
   sendAndDraw()
   {
      ajaxRequest(this.query, function(response) {
         if (response.success == true)
         {
            this.handleResponse(response);
         }
         else
         {
            console.log("Call to retrieve chart data failed.");
         }
      }.bind(this));
   }

   handleResponse(response)
   {
      /*
      this.currentDataTable = null;
  
      if (response.isError())
      {
         this.handleErrorResponse(response);
      }
      else
      {
         this.currentDataTable = response.getDataTable();
         console.log(this.currentDataTable);
         
         
         //this.currentDataTable = new google.visualization.DataTable();
         //this.currentDataTable.addColumn('date', 'Date');
         //this.currentDataTable.addColumn('number', 'Price');
         //this.currentDataTable.addRows([[new Date("2022-01-01"), 1], [new Date("2022-02-01"), 2], [new Date("2022-03-01"), 3]])
         
         this.draw();
      }
      */
      
      this.currentDataTable = new google.visualization.DataTable();
      
      if (typeof response.range !== 'undefined')
      {
         if (typeof this.options.hAxis == 'undefined')
         {
            this.options.hAxis = {};
         }
         
         this.options.hAxis.viewWindow = {
            min: new Date(response.range.min), 
            max: new Date(response.range.max)
         };
      }
      
      for (const column of response.columns)
      {
         this.currentDataTable.addColumn(column.type, column.label);
      }
      
      this.currentDataTable.addRows(Chart.getRowsFromData(response.rows));
      
      // Note: Necessary because the date data is provided in "Y-m-d" format and Google Charts 
      //       defaults to outputing dates in the local timezone.  Formatting with "timeZone: 0" outputs
      //       the dates as they were provided, with no timezone conversion.
      var formatter = new google.visualization.DateFormat({timeZone: 0, pattern: "MMM d, yyyy"});
      formatter.format(this.currentDataTable, 0);  // Format column 0.
      
      this.draw();
   }
   
   handleErrorResponse(response)
   {
      var message = response.getMessage();
      var detailedMessage = response.getDetailedMessage();
      
      if (this.errorContainer)
      {
         google.visualization.errors.addError(this.errorContainer, message, detailedMessage, {'showInTooltip': false});
      }
      else
      {
         throw Error(message + ' ' + detailedMessage);
      }
   }
   
   abort()
   {
      this.query.abort();
   }
   
   static getRowsFromData(data)
   {
      var rows = [];
      
      for (var key in data)
      {
         rows.push([new Date(key), data[key]]);
      }
      
      return (rows);
   }
}
