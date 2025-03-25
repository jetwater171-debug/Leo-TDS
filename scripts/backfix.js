//use the script like this:
/*
<script src="backfix.js" 
  data-backlink="https://example.com?subid={subid}" 
  data-showcaselink="https://example2.com"
  data-traceenabled="false"
  data-redirect="false"
  data-isoff="false">
</script>
 */
const scriptTag = document.currentScript;
//If redirect is true, then after back button click there will be a redirect to the backLink.
//Else backLink will be shown using an iframe
const redirect = scriptTag.hasAttribute("data-redirect")
  ? scriptTag.getAttribute("data-redirect") == "true"
  : false;
//An url that the user will see using and iframe or redirect
const backLink = scriptTag.getAttribute("data-backlink");
//An url that the user will see in an iframe after (s)he clicks Back SECOND TIME
const showcaseLink = scriptTag.getAttribute("data-showcaselink") ?? backLink;
//If true, then debug info will be printed to console
const traceEnabled = scriptTag.hasAttribute("data-traceenabled")
  ? scriptTag.getAttribute("data-traceenabled") == "true"
  : false;
//Using "true" for this attribute you can temporary turn off the script
const isOff = scriptTag.hasAttribute("data-isoff")
  ? scriptTag.getAttribute("data-isoff") == "true"
  : false;

//Don't edit anything down below if you are not sure, what you are doing!
document.addEventListener("DOMContentLoaded", function() {
  if (isOff) {
    trace("BackFix switched OFF! Exiting...");
    return;
  }
  const frameName = "LandFrame";
  const showcaseFrameName = "ShowcaseFrame";

  trace(
    "Back Button Fix v0.4 by Yellow Web (https://yellowweb.top)",
    "font-size:25px;color:yellow;font-weight:bold",
  );

  if (!traceEnabled && isLocalHost()) {
    trace("Localhost found!");
    return;
  }
  if (isInIframe()) {
    trace("We are in frame!");
    return;
  }
  trace(`Back link is: ${backLink}`);
  trace(`Mode is: ${redirect ? "Redirect" : "Iframe"}`);
  if (!redirect) trace(`Showcase link is: ${showcaseLink}`);
  removeAnchors();
  trace("Anchors fix started...");
  backInFrame(backLink);

  function backInFrame() {
    if (!isIos()) {
      trace("Not IOs, cheching gesture!");
      checkUserGesture(function() {
        pushState();
        trace("Pushed state after gesture.");
        createFrame(frameName, backLink);
      });
    } else {
      trace("IOs found!");
      pushState();
      trace("Pushed state.");
      createFrame(frameName, backLink);
    }

    window.onpopstate = function(t) {
      trace(`Popped state!`);
      t.preventDefault();
      if (!isIos() && !!!t.state) {
        trace("OnPopState: Not IOs and no state!");
        return;
      }
      trace(`Popped state: ${JSON.stringify(t.state)}`);
      switch (t.state.cpa) {
        case 1:
          trace("Time to show landing!");
          if (redirect) {
            window.location.href = backLink;
          } else {
            showFrame(frameName);
            createFrame(showcaseFrameName, showcaseLink);
          }
          break;
        case 0:
          trace("Time to show showcase!");
          showFrame(showcaseFrameName);
          break;
        default:
          break;
      }
    };
  }

  function pushState() {
    for (var t = 0; t < 3; t++) {
      let s = { cpa: t };
      window.history.pushState(s, "", window.location);
    }
  }

  function createFrame(name, url) {
    if (redirect) {
      trace("Creating prerender for redirect.");
      let prerender = document.createElement("link");
      prerender.rel = "prerender";
      prerender.href = backLink;
      document.head.appendChild(prerender);
    } else {
      var nodeFrame = document.getElementById(name);
      if (nodeFrame) nodeFrame.parentNode.removeChild(nodeFrame);
      var frame = document.createElement("iframe");
      frame.style.width = "100%";
      frame.id = name;
      frame.name = name;
      frame.style.height = "100vh";
      frame.style.position = "fixed";
      frame.style.top = 0;
      frame.style.left = 0;
      frame.style.border = "none";
      frame.style.zIndex = 999997;
      frame.style.display = "none";
      frame.style.backgroundColor = "#fff";
      document.body.append(frame);
      frame.src = url;
      frameWindow = frame.contentWindow;
      frameWindow.console.log = function() { };
      trace(`Created frame ${name} for ${url}!`);
    }
  }

  function showFrame(name) {
    var nodeFrame = document.getElementById(name);
    nodeFrame.style.display = "block";
    document.body.style.overflow = "hidden";
    document.querySelectorAll(`body > *:not(#${name})`).forEach(function(e) {
      e.setAttribute("style", "display:none;");
    });
    trace(`Frame ${name} displayed!`);
  }

  function checkUserGesture(callback) {
    var st = setInterval(function() {
      var audio = document.createElement("audio");
      var playPromise = audio.play();
      if (playPromise instanceof Promise) {
        if (!audio.paused) {
          clearInterval(st);
          callback();
        }
        playPromise.then(function() { }).catch(function() { });
      } else {
        if (!audio.paused) {
          clearInterval(st);
          callback();
        }
      }
    }, 100);
  }

  function removeAnchors() {
    setInterval(function() {
      const anchors = document.querySelectorAll('a[href*="#"]');
      for (let anchor of anchors) {
        anchor.removeAttribute("onclick");
        anchor.addEventListener("click", function(e) {
          e.preventDefault();
          const blockID = anchor.getAttribute("href").substring(1);
          document.getElementById(blockID).scrollIntoView({
            behavior: "smooth",
            block: "start",
          });
        });
      }
    }, 1000);
  }

  function isInIframe() {
    try {
      return (
        window != window.top ||
        document != top.document ||
        self.location != top.location
      );
    } catch (e) {
      return true;
    }
  }

  function isIos() {
    return /(iPad|iPod|iPhone|Mac)/i.test(navigator.platform);
  }

  function isLocalHost() {
    return (
      window.location.host.includes("localhost") ||
      window.location.host.includes("127.0.0.1") ||
      window.location.protocol === "file:"
    );
  }
  function trace(msg, style = null) {
    if (!traceEnabled) return;
    if (style == null) console.log("Backfix: " + msg);
    else {
      console.log("%c" + msg, style);
    }
  }
});
