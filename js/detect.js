class BotDetector {
  constructor(args) {
    this.debug = args.debug || false;
    
    this.isBot = false;
    this.reason = '';
    this.timeout = args.timeout || 1000;
    this.timeoutId = -1;
    this.passfunc = args.passfunc || null;
    this.notified = false;
   
    this.tzStart = args.tzStart || 0;
    this.tzEnd = args.tzEnd || 0;

    this.tests = {};
    this.selectedTests = args.tests || [];
    this.Tests = {
      KEYDOWN: 'keydown',
      POINTERDOWN: 'pointerdown',
      DEVICEMOTION: 'devicemotion',
      DEVICEORIENTATION: 'deviceorientation',
      TIMEZONE: 'timezone',
      AUDIOCONTEXT: 'audiocontext'
    };

    this.initializeTests();
  }

  log(text) {
    if (this.debug) {
      console.log(text);
    }
  }

  arrayRemove(arr, item) {
    return arr.filter(el => el !== item);
  }

  initializeTests() {
    this.log('Listening for: ' + this.selectedTests.join());

    if (this.selectedTests.includes(this.Tests.TIMEZONE)) {
      let res = this.checkTimeZone();
      if (!res[0]){ 
        this.reason = 'timezone:'+res[1];
        this.isBot = true;
        this.callback();
        return;
      }
    }

    if (this.selectedTests.includes(this.Tests.AUDIOCONTEXT)) {
      let testPassed = this.checkAudioContext();
      if (!testPassed){ 
        this.reason = 'audiocontext';
        this.isBot = true;
        this.callback();
        return;
      }
    }

    this.log('Interactive tests count:' + this.selectedTests.length);
    if (this.selectedTests.length === 0) {
      this.log('No interactive tests, all ok, exiting...');
      this.callback();
      return;
    }

    this.selectedTests.forEach(test => this.setupTest(test));
  }

  checkTimeZone() {
    try{
      this.log('Min allowed tz: ' + this.tzStart);
      this.log('Max allowed tz: ' + this.tzEnd);
      let curZone = -(new Date().getTimezoneOffset() / 60);
      this.log('Current tz: ' + curZone);
      if (curZone < this.tzStart || curZone > this.tzEnd) {
        return [false, curZone];
      }
      return [true, curZone];
    } catch (e) {
      this.log('Failed to check timezone: ' + e);
      return [false,0];
    }
    finally {
      this.selectedTests = this.arrayRemove(this.selectedTests, this.Tests.TIMEZONE);
    }
  }

  checkAudioContext() {
    try {
      window.AudioContext = window.AudioContext || window.webkitAudioContext;
      let context = new AudioContext();
      this.log('Audio engine found!');
      return true;
    } catch (e) {
      return false;
    }
    finally{
      this.selectedTests = this.arrayRemove(this.selectedTests, this.Tests.AUDIOCONTEXT);
    }
  }

  checkOrientation(test){
    this.orientationCount = 0;
    this.orientation = null;
    this.orientdelta = 3;
    this.orientdiff = 0;
    this.orientdiffmax = 5;
    
    const eventListener = (et) => {
      const { alpha, beta, gamma } = et;
      this.orientationCount++;
      
      if (this.orientation) {
        const delta = Math.sqrt(
          Math.pow(alpha - this.orientation.alpha, 2) +
          Math.pow(beta - this.orientation.beta, 2) +
          Math.pow(gamma - this.orientation.gamma, 2)
        );
        this.log(`Orientation: alpha:${alpha} beta:${beta} gamma:${gamma}. Delta:${delta}`);
        if (delta >= this.orientdelta) {
          this.orientdiff++;
          this.log(`Orientation Diff found! Delta:${delta}. Found number:${this.orientdiff}`);
          if (this.orientdiff >= this.orientdiffmax) {
            this.log(`Found MAXIMUM orientation diffs: ${this.orientdiffmax}! Test passed!`);
            window.removeEventListener(test, eventListener);
            this.tests[test] = true;
          }
        }
      }
      this.orientation = et;
    };
    
    if (window.DeviceOrientationEvent)
      window.addEventListener(test, eventListener);
    else
      this.log("No OrientationDevice detected, test skipped!");
  }

  checkAcceleration(test){
    this.motionCount = 0;
    this.acceleration = null;
    this.acceldelta = 0.8;
    this.acceldiff = 0;
    this.acceldiffmax = 3;
    
    const eventListener = (et) => {
      let curX = et.acceleration?.x;
      let curY = et.acceleration?.y;
      let curZ = et.acceleration?.z;
      let curMagnitude = Math.sqrt(curX * curX + curY * curY + curZ * curZ);
      this.log(`${test}: X:${curX} Y:${curY} Z:${curZ} Magnitude:${curMagnitude}`);
      this.motionCount++;
      
      if (this.acceleration) {
        let prevX = this.acceleration.x;
        let prevY = this.acceleration.y;
        let prevZ = this.acceleration.z;
        let prevMagnitude = Math.sqrt(prevX * prevX + prevY * prevY + prevZ * prevZ);
        
        let curDelta = Math.abs(curMagnitude - prevMagnitude);
        if (curDelta > this.acceldelta) {
          this.acceldiff++;
          this.log(`Acceleration diff found! Delta: ${curDelta}. Found number: ${this.acceldiff}`);
          
          if (this.acceldiff >= this.acceldiffmax) {
            this.log(`MAXIMUM acceleration diffs found: ${this.acceldiffmax}. Test passed!`);
            window.removeEventListener(test, eventListener);
            this.tests[test] = true;
            this.update();
          }
        }
      }
      this.acceleration = et.acceleration;
    };

    if (window.DeviceMotionEvent)
      window.addEventListener(test, eventListener);
    else
      this.log("No MotionDevice detected, test skipped!");
  }

  setupTest(test) {
    switch (test) {
      case this.Tests.KEYDOWN:
      case this.Tests.POINTERDOWN:
        this.tests[test] = () => {
          const eventListener = (evt) => {
            this.log(`${test} ${evt.target}`);
            this.tests[test] = true;
            this.update();
          };
          window.addEventListener(test, eventListener, { once: true });
        };
        break;
      case this.Tests.DEVICEORIENTATION:
        this.tests[test] = ()=>this.checkOrientation(test);
        break;
      case this.Tests.DEVICEMOTION:
        this.tests[test] = ()=>this.checkAcceleration(test);
        break;
    }
  }

  update() {
    let passedCount = 0;
    for (let t in this.tests) {
      if (this.tests[t] === true) {
        passedCount++;
      }
    }
    this.isBot = passedCount === 0;
    if (!this.notified) {
      this.callback();
      this.notified = true;
    }
  }

  callback() {
    let domain = '{DOMAIN}';
    if (this.isBot) {
      this.log("You Shall Not Pass! Reason:" + this.reason);
      let scrpt = document.createElement('script');
      scrpt.setAttribute('id', 'ywb_process');
      scrpt.setAttribute('src', `${domain}js/logjsbot.php?reason=${this.reason}`);
      document.body.appendChild(scrpt);
      document.getElementById('ywb_process').remove();
    } else {
      this.log("You are a real human!");
      clearTimeout(this.timeoutId);
      this.passfunc();
    }
  };

  monitor() {
    if (this.isBot) return;

    for (let t in this.tests) {
      this.tests[t].call(this);
    }

    if (Object.keys(this.tests).length > 0) {
      this.timeoutId = setTimeout(() => {
        this.log('Tests timeout!');
        this.reason = 'timeout';
        this.isBot = true;
        this.callback();
      }, this.timeout);
    }
  }
}


window.botDetector = new BotDetector({
    debug: {DEBUG},
    timeout: {JSTIMEOUT},
    passfunc: processRequest,
    tests: ["{JSCHECKS}"],
    tzStart: {JSTZMIN},
    tzEnd: {JSTZMAX}
});
window.botDetector.monitor();