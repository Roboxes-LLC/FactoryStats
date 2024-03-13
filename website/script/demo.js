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
      var requestUrl = "api/workstationSummary/?shiftId=" + getShiftId();
      
      ajaxRequest(requestUrl, function(response) {
         if (response.success == true)
         {
            console.log(response);
            this.stationIds = [];

            for (var i = 0; i < response.workstationSummary.length; i++)
            {
               this.stationIds[i] = response.workstationSummary[i].stationId;
            }
         }
         else
         {
            console.log("Call to fetch station ids failed.");
         }
      }.bind(this));
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
      var requestUrl = "api/update/?stationId=" + stationId + "&shiftId=" + shiftId + "&count=1";
      
      ajaxRequest(requestUrl, function(response) {
         if (response.success == true)
         {
            update();
         }
         else
         {
            console.log("Call to increment count failed.");
         }
      }.bind(this));
   }   
}