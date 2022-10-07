class Display
{
   static SLOW_PING_INTERVAL = (30 * 1000);  // 30 seconds
   
   static FAST_PING_INTERVAL = (10 * 1000);  // 10 seconds
   
   static PING_FAIL_THRESHOLD = 3;
   
   // HTML elements
   static PageElements = {
      "DISPLAY_DISCONNECTED": "display-disconnected",
      "DISPLAY_UNAUTHORIZED": "display-unauthorized",
      "DISPLAY_UNREGISTERED": "display-unregistered",
      "DISPLAY_REDIRECTING":  "display-redirecting",
      "DISPLAY_UNCONFIGURED": "display-unconfigured",
      "UID":                  "uid",
      "SUBDOMAIN":            "subdomain"
   };
   
   constructor(containerId)
   {
      this.displayState = DisplayState.UNKNOWN;
      
      this.subdomain = null;
      
      this.uid = this.retrieveUid();

      this.setUidElements(this.uid);
      
      this.fullyConfigured = false;
      
      this.slideShow = new SlideShow(containerId);
      
      this.pingFailCount = 0;
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
      
      try
      {
         ajaxRequest(
            pingUrl, 
            function(response){this.onPingReply(response)}.bind(this),
            function(error){this.onPingFailure(error)}.bind(this));
      }
      catch (exception)
      {
         this.onPingFailure(exception);
      }
   }
   
   onPingReply(response)
   {
      if (response.success)
      {
         // uid
         if (typeof response.uid !== 'undefined')
         {
            console.log("Display.onPingReply: UID = " + response.uid);
            
            this.uid = response.uid;
            
            this.storeUid(this.uid);
            
            this.setUidElements(this.uid);
         }
         
         // subdomain
         if ((typeof response.subdomain !== 'undefined') &&
             (this.subdomain != response.subdomain))
         {
            console.log("Display.onPingReply: subdomain = " + response.subdomain);
            
            this.subdomain = response.subdomain;
            
            this.setSubdomainElements(this.subdomain );
         }
         
         // displayState
         if ((typeof response.displayState !== 'undefined') &&
             (this.displayState != response.displayState))
         {
            console.log("Display.onPingReply: displayState = " + response.displayState);
            
            this.setDisplayState(response.displayState);
         }
         
         // presentation
         if (typeof response.presentation !== 'undefined')
         {
            let slideShowConfig = new SlideShowConfig();
            slideShowConfig.initializeFromTabRotateConfig(response.presentation);
            
            this.slideShow.onConfigUpdate(slideShowConfig);
         }
         else
         {
            this.slideShow.onConfigUpdate(new SlideShowConfig());
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
      
      this.pingFailCount = 0;
   }
   
   onPingFailure(e)  // Exception or error
   {            
      this.pingFailCount++;

      console.log("Display.onPingFailure: " + this.pingFailCount + " failures");
      
      if (this.pingFailCount >= Display.PING_FAIL_THRESHOLD)
      {
         this.slideShow.onConfigUpdate(new SlideShowConfig());
         
         this.setDisplayState(DisplayState.DISCONNECTED);
      }
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
   
   setUidElements(uid)
   {
      var elements = document.getElementsByClassName("uid");
      for (var element of elements)
      {
         element.innerHTML = uid;
      }
   }
   
   setSubdomainElements(subdomain)
   {
      var elements = document.getElementsByClassName("subdomain");
      
      for (var element of elements)
      {
         element.innerHTML = subdomain;
      }
   }
   
   setDisplayState(displayState)
   {      
      this.displayState = displayState;
      
      switch (this.displayState)
      {
         case DisplayState.READY:
         case DisplayState.DISABLED:
         {
            this.hideInstructions();
            break;
         }

         case DisplayState.DISCONNECTED:
         {
            this.showInstructions(Display.PageElements.DISPLAY_DISCONNECTED);
            break;
         }
         
         case DisplayState.UNAUTHORIZED:
         {
            this.showInstructions(Display.PageElements.DISPLAY_UNAUTHORIZED);
            break;
         }
         
         case DisplayState.UNREGISTERED:
         {
            this.showInstructions(Display.PageElements.DISPLAY_UNREGISTERED);
            break;
         }
         
         case DisplayState.REDIRECTING:
         {
            this.showInstructions(Display.PageElements.DISPLAY_REDIRECTING);
            break;
         }
         
         case DisplayState.UNCONFIGURED:
         {
            this.showInstructions(Display.PageElements.DISPLAY_UNCONFIGURED);
            break;
         }
         
         default:
         {
            console.log("Invalid display state: " + displayState);
         }
      }
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
   
   hideInstructions()
   {
      var elements = document.getElementsByClassName("instructions");
      for (var element of elements)
      {
         element.classList.remove("show");
      }
   }
   
   showInstructions(elementId)
   {
      // Hide all instructions.
      this.hideInstructions();
      
      // Show the selected instruction.
      document.getElementById(elementId).classList.add("show");
   }
}