const scriptTag = document.currentScript;
const redirect = scriptTag.hasAttribute("data-redirect")
  ? scriptTag.getAttribute("data-redirect") == "true"
  : false;
const backLink = scriptTag.getAttribute("data-backlink");
const secondLink = scriptTag.getAttribute("data-secondlink") ?? backLink;
const traceEnabled = scriptTag.hasAttribute("data-traceenabled")
  ? scriptTag.getAttribute("data-traceenabled") == "true"
  : false;

document.addEventListener("DOMContentLoaded", function() {
  const frameName = "LandFrame";
  const secondFrameName = "SecondFrame";

  trace(
    "Back Button Fix v0.5.2 by Yellow Web",
    "font-size:25px;color:yellow;font-weight:bold",
  );

  if (isLocalHost()) {
    if (traceEnabled) trace("Localhost found!");
    else return;
  }
  if (isInIframe()) {
    trace("We are in frame!");
    //return;
  }
  trace(`Back link is: ${backLink}`);
  trace(`Mode is: ${redirect ? "Redirect" : "Iframe"}`);
  if (!redirect) trace(`Second link is: ${secondLink}`);
  removeAnchors();
  trace("Anchors fix started...");
  backInFrame(backLink);

  // Carousel views: 0=landing, 1=first(backLink), 2=second(secondLink)
  // Going back cycles: landing -> backLink -> secondLink -> landing -> ...
  // Going forward reverses: ... -> secondLink -> backLink -> landing
  const DEPTH = 50;

  function backInFrame() {
    function init() {
      createFrame(frameName, backLink);
      var idle = window.requestIdleCallback || function(cb) { setTimeout(cb, 1); };
      idle(function() { createFrame(secondFrameName, secondLink); });
      // Push states with pre-computed view for each level
      // Top of stack (last pushed) = landing (current page, view 0)
      // One back = backLink (view 1), two back = secondLink (view 2), etc.
      // We push deepest first, then work up to landing on top
      for (var i = DEPTH; i >= 1; i--) {
        var view = i % 3; // 1=backLink, 2=secondLink, 0=landing
        window.history.pushState({ bf: true, view: view }, "", window.location);
      }
      // Final state on top = landing (where user is now)
      window.history.pushState({ bf: true, view: 0 }, "", window.location);
      trace(`Pushed ${DEPTH + 1} states. User is at top (landing).`);
    }

    if (!isIos()) {
      trace("Not IOs, cheching gesture!");
      checkUserGesture(function() {
        init();
        trace("Initialized after gesture.");
      });
    } else {
      trace("IOs found!");
      init();
      trace("Initialized.");
    }

    window.onpopstate = function(t) {
      if (!t.state || !t.state.bf) {
        trace("OnPopState: not our state!");
        return;
      }

      trace(`Popped state: view=${t.state.view}`);

      if (redirect) {
        window.location.href = backLink;
        return;
      }

      showView(t.state.view);
    };
  }

  function showView(viewIndex) {
    if (viewIndex === 0) {
      // Show landing (original page)
      trace("Showing landing page.");
      var f1 = document.getElementById(frameName);
      var f2 = document.getElementById(secondFrameName);
      if (f1) f1.style.display = "none";
      if (f2) f2.style.display = "none";
      document.querySelectorAll("body > *").forEach(function(e) {
        if (e.id !== frameName && e.id !== secondFrameName) {
          e.style.display = "";
        }
      });
      document.body.style.overflow = "";
    } else {
      var nameToShow = viewIndex === 1 ? frameName : secondFrameName;
      showFrame(nameToShow);
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
      trace(`Created frame ${name} for ${url}!`);
    }
  }

  function showFrame(name) {
    var nodeFrame = document.getElementById(name);
    nodeFrame.style.display = "block";
    document.body.style.overflow = "hidden";
    document.querySelectorAll(`body > *:not(#${name})`).forEach(function(e) {
      e.style.display = "none";
    });
    trace(`Frame ${name} displayed!`);
  }

  function checkUserGesture(callback) {
    var audio = document.createElement("audio");
    var st = setInterval(function() {
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
        if (anchor.dataset.bfFixed) continue;
        anchor.dataset.bfFixed = "1";
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