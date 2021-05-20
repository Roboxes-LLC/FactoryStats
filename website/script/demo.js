class Demo
{
   constructor()
   {
      this.MIN_INTERVAL = 1;
      this.MAX_INTERVAL = 5;
      
      this.stationIds = null;
   }
    
   startSimulation()
   {
      this.fetchStationIds();
      
      this.initiateNextInterval();   
   }
   
   // **************************************************************************
   
   fetchStationIds()
   {
      var requestURL = "api/workstationSummary/?shiftId=" + getShiftId();
         
      var xhttp = new XMLHttpRequest();
      xhttp.demo = this;
      xhttp.onreadystatechange = function()
      {
         if (this.readyState == 4 && this.status == 200)
         {
            try
            {
               var json = JSON.parse(this.responseText);
               
               this.demo.stationIds = [];
   
               for (var i = 0; i < json.workstationSummary.length; i++)
               {
                  this.demo.stationIds[i] = json.workstationSummary[i].stationId;
               }
            }
            catch (exception)
            {
               console.log("JSON syntax error");
               console.log(this.responseText);
            }         
         }
      };
      xhttp.open("GET", requestURL, true);
      xhttp.send();
   }
   
   initiateNextInterval()
   {
      var period = Math.floor(Math.random() * (this.MAX_INTERVAL - this.MIN_INTERVAL)) + this.MIN_INTERVAL;
      
      setTimeout(function(){
         if (this.stationIds != null)
         {
            this.simulateUpdate();
         }
         this.initiateNextInterval();
      }.bind(this), (period * 1000));
   }
   
   simulateUpdate()
   {
      var index = Math.floor(Math.random() * this.stationIds.length);
      var stationId = this.stationIds[index];
      
      var shiftId = Demo.getShiftId();
      
      Demo.incrementCount(shiftId, stationId); 
   }
   
   static getShiftId()
   {
      return (parseInt(document.getElementById("shift-id-input").value));
   }
   
   static incrementCount(shiftId, stationId)
   {
      var requestURL = "api/update/?stationId=" + stationId + "&shiftId=" + shiftId + "&count=1";
      
      var xhttp = new XMLHttpRequest();
      xhttp.onreadystatechange = function()
      {
         if (this.readyState == 4 && this.status == 200)
         {
            var json = JSON.parse(this.responseText);
   
            update();
         }
      };
      xhttp.open("GET", requestURL, true);
      xhttp.send(); 
   }   
}