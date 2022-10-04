class Slide
{
   constructor(url, duration, reloadPeriod)
   {
      this.url = url;
      this.duration = duration;
      this.reloadPeriod = reloadPeriod;
      this.reloadTimer = null;

      // Create iFrame;
      this.iFrame = document.createElement('iframe');
      this.iFrame.src = url;
      this.iFrame.style = "position:fixed; top:0; left:0; bottom:0; right:0; width:100%; height:100%; border:none; margin:0; padding:0; overflow:hidden; z-index:999999;";
      this.iFrame.frameBorder = 0;
      this.iFrame.style.opacity = 0;

      if (this.reloadPeriod > 0)
      {
         this.reloadTimer = setInterval(function(){this.reload()}.bind(this), (this.reloadPeriod * 1000));
      }
   }

   reload()
   {
      if (this.iFrame != null)
      {
         console.log("reload: "+ this.url);
         this.iFrame.src = this.iFrame.src;
      }
   }
}

class SlideConfig
{
   constructor(url, duration, reload)
   {
      this.url = url;
      this.duration = duration;
      this.reload = reload;
   }
}

class SlideShowConfig
{
   constructor()
   {
      this.slides = [];
   }
   
   initializeFromTabRotateConfig(tabRotateConfig)
   {
      for (var website of tabRotateConfig.websites)
      {
         this.slides.push(new SlideConfig(website.url, website.duration, website.tabReloadIntervalSeconds));
      }
   }
}

class SlideShow
{
   constructor(containerId)
   {
      this.container = document.getElementById(containerId);

      this.slides = [];
      this.isRunning = false;
      this.currentSlideIndex = -1;
      this.slideTimer = null;
      this.reloadTimer = null;
      this.reload = null;
   }

   setConfig(config)
   {
      this.onConfigUpdate(config);
   }

   start()
   {
      console.log("SlideShow.start");
      this.isRunning = true;
      clearTimeout(this.slideTimer);
      this.currentSlide = this.slides.length;

      this.nextSlide();
   }

   stop()
   {
      console.log("SlideShow.stop");
      this.isRunning = false;
      clearTimeout(this.slideTimer);
   }

   setReload(reload, reloadPeriod)
   {
      if (this.reloadTimer != null)
      {
         clearInterval(this.reloadTimer);
      }

      this.reload = reload;
      this.reloadTimer = setInterval(function(){this.reload()}.bind(this), (reloadPeriod * 1000));
   }

   // ********************************************

   onSlideTimeout()
   {
      if (this.isRunning)
      {
         this.nextSlide();
      }
   }

   nextSlide()
   {
      if (this.slides.length > 0)
      {
         var currentSlide = null;
         if ((this.currentSlideIndex >= 0) &&
             (this.currentSlideIndex < this.slides.length))
         {
            currentSlide = this.slides[this.currentSlideIndex];
         }

         this.currentSlideIndex++;
         if (this.currentSlideIndex >= this.slides.length)
         {
            this.currentSlideIndex = 0;
         }

         var nextSlide = this.slides[this.currentSlideIndex];

         this.transition(currentSlide, nextSlide);

         if (nextSlide.duration > 0)
         {
            this.slideTimer = setTimeout(function(){this.onSlideTimeout()}.bind(this), (nextSlide.duration * 1000));
         }
      }
   }

   transition(currentSlide, nextSlide)
   {
      if (currentSlide != null)
      {
         //currentSlide.iFrame.style.display = "none";
         currentSlide.iFrame.style.opacity = 0;
      }

      if (nextSlide != null)
      {
         //nextSlide.iFrame.style.display = "block";
         console.log(nextSlide.url);
         nextSlide.iFrame.style.opacity = 1;
      }
   }

   onPauseKey()
   {
      if (!this.isRunning)
      {
         this.start();
      }
      else
      {
         this.stop();
      }
   }

   onConfigUpdate(config)
   {
      if (!SlideShow.configEqual(config, this.config))
      {
         console.log("SlideShow.onConfigUpdate: " + config);

         this.config = config;

         this.slides = [];

         var iFrames = [];

         for (var slideConfig of this.config.slides)
         {
            var slide = new Slide(slideConfig.url, slideConfig.duration, slideConfig.reload);

            this.slides.push(slide);
            iFrames.push(slide.iFrame);
         }

         // Replace all iFrames.
         this.container.replaceChildren(...iFrames);

         if (this.isRunning)
         {
            this.stop();
         }
         
         this.start();
      }
   }

   static configEqual(left, right)
   {
      return (JSON.stringify(left) === JSON.stringify(right));
   }
}