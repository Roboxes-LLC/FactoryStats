class Display
{
   static SLOW_PING_INTERVAL = (30 * 1000);  // 30 seconds
   
   static FAST_PING_INTERVAL = (10 * 1000);  // 30 seconds
   
   constructor(containerId)
   {
      this.customerId = null;
      
      this.uid = this.retrieveUid();
      
      this.fullyConfigured = false;
      
      this.slideShow = new SlideShow(containerId);
   }
   
   start()
   {
      console.log("Display.start");
      
      this.isRunning = true;
      
      this.ping();
      this.displayTimer = setInterval(function(){this.ping()}.bind(this), Display.FAST_PING_INTERVAL);      
   }

   stop()
   {
      console.log("Display.stop");
      
      this.isRunning = false;
      
      clearTimeout(this.displayTimer);
   }
   
   // **************************************************************************
   
   ping()
   {
      console.log("Display.ping");
      
      var pingUrl = "/api/display2/";
      
      if (this.uid != null)
      {
         pingUrl = pingUrl + "?uid=" + this.uid;
      }
      else
      {
         pingUrl = pingUrl + "?generateUid=true";
      }
      
      if (this.customerId != null)
      {
         pingUrl = pingUrl + "&customerId=" + this.customerId;
      }
      
      try
      {
         ajaxRequest(pingUrl, function(response){this.onPingReply(response)}.bind(this));
      }
      catch (exception)
      {
         this.onPingFailure();
      }
   }
   
   onPingReply(response)
   {
      console.log(response);
      if (response.success)
      {
         // uid
         if (typeof response.uid !== 'undefined')
         {
            console.log("Display.onPingReply: UID = " + response.uid);
            
            this.uid = response.uid;
            this.storeUid(this.uid);
         }
         
         // customerId
         if ((typeof response.customerId !== 'undefined') &&
             (this.customerId != response.customerId))
         {
            console.log("Display.onPingReply: customerId = " + response.customerId);
            
            this.customerId = response.customerId;
         }
         
         // presentation
         if (typeof response.presentation !== 'undefined')
         {
            let slideShowConfig = new SlideShowConfig();
            slideShowConfig.initializeFromTabRotateConfig(response.presentation);
            
            this.slideShow.onConfigUpdate(slideShowConfig);
            
            if (!this.slideShow.isRunning)
            {
               this.slideShow.start();
            }
         }
         
         // fullyConfigured
         let wasFullyConfigured = this.fullyConfigured;
         this.fullyConfigured = (typeof response.fullyConfigured !== 'undefined');
         
         if (this.fullyConfigured != wasFullyConfigured)
         {
            this.resetPingTimer(this.fullyConfigured);   
         }
         
         // resetPending
         if (typeof response.resetPending !== 'undefined')
         {
            this.reset();
         }
      }
   }
   
   onPingFailure(exception)
   {
      console.log("Display.onPingFailure");
   }
   
   retrieveUid()
   {
      let cookieUid = getCookie("uid");
      
      return ((cookieUid == "") ? null : cookieUid);
   }
   
   storeUid(uid)
   {
      setCookie("uid", uid);
   }
   
   reset()
   {
      console.log("Display.reset: Resetting in 5 seconds ...");
      
      setTimeout(function() {
         location.reload();   
      },
      5000);
   }
   
   resetPingTimer(fullyConfigured)
   {
      // Reset the ping interval.
      let pingInterval = (this.fullyConfigured ? Display.SLOW_PING_INTERVAL : Display.FAST_PING_INTERVAL);
      
      console.log("Display.resetPingTimer: Resetting ping timer to " + (pingInterval / 1000) + " seconds");
      
      clearTimeout(this.displayTimer);
      
      this.displayTimer = setInterval(function(){this.ping()}.bind(this), pingInterval);   
   }
}