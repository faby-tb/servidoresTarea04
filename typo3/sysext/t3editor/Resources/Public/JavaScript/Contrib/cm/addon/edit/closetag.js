!function(e){"object"==typeof exports&&"object"==typeof module?e(require("../../lib/codemirror"),require("../fold/xml-fold")):"function"==typeof define&&define.amd?define(["../../lib/codemirror","../fold/xml-fold"],e):e(CodeMirror)}((function(e){e.defineOption("autoCloseTags",!1,(function(a,l,s){if(s!=e.Init&&s&&a.removeKeyMap("autoCloseTags"),l){var d={name:"autoCloseTags"};("object"!=typeof l||l.whenClosing)&&(d["'/'"]=function(t){return function(t){return t.getOption("disableInput")?e.Pass:o(t,!0)}(t)}),("object"!=typeof l||l.whenOpening)&&(d["'>'"]=function(o){return function(o){if(o.getOption("disableInput"))return e.Pass;for(var a=o.listSelections(),l=[],s=o.getOption("autoCloseTags"),d=0;d<a.length;d++){if(!a[d].empty())return e.Pass;var c=a[d].head,f=o.getTokenAt(c),g=e.innerMode(o.getMode(),f.state),u=g.state,m=g.mode.xmlCurrentTag&&g.mode.xmlCurrentTag(u),h=m&&m.name;if(!h)return e.Pass;var p="html"==g.mode.configuration,C="object"==typeof s&&s.dontCloseTags||p&&t,b="object"==typeof s&&s.indentTags||p&&n;f.end>c.ch&&(h=h.slice(0,h.length-f.end+c.ch));var v=h.toLowerCase();if(!h||"string"==f.type&&(f.end!=c.ch||!/[\"\']/.test(f.string.charAt(f.string.length-1))||1==f.string.length)||"tag"==f.type&&m.close||f.string.indexOf("/")==c.ch-f.start-1||C&&r(C,v)>-1||i(o,g.mode.xmlCurrentContext&&g.mode.xmlCurrentContext(u)||[],h,c,!0))return e.Pass;var y="object"==typeof s&&s.emptyTags;if(y&&r(y,h)>-1)l[d]={text:"/>",newPos:e.Pos(c.line,c.ch+2)};else{var x=b&&r(b,v)>-1;l[d]={indent:x,text:">"+(x?"\n\n":"")+"</"+h+">",newPos:x?e.Pos(c.line+1,0):e.Pos(c.line,c.ch+1)}}}var P="object"==typeof s&&s.dontIndentOnAutoClose;for(d=a.length-1;d>=0;d--){var T=l[d];o.replaceRange(T.text,a[d].head,a[d].anchor,"+insert");var j=o.listSelections().slice(0);j[d]={head:T.newPos,anchor:T.newPos},o.setSelections(j),!P&&T.indent&&(o.indentLine(T.newPos.line,null,!0),o.indentLine(T.newPos.line+1,null,!0))}}(o)}),a.addKeyMap(d)}}));var t=["area","base","br","col","command","embed","hr","img","input","keygen","link","meta","param","source","track","wbr"],n=["applet","blockquote","body","button","div","dl","fieldset","form","frameset","h1","h2","h3","h4","h5","h6","head","html","iframe","layer","legend","object","ol","p","select","table","ul"];function o(t,n){for(var o=t.listSelections(),r=[],a=n?"/":"</",l=t.getOption("autoCloseTags"),s="object"==typeof l&&l.dontIndentOnSlash,d=0;d<o.length;d++){if(!o[d].empty())return e.Pass;var c=o[d].head,f=t.getTokenAt(c),g=e.innerMode(t.getMode(),f.state),u=g.state;if(n&&("string"==f.type||"<"!=f.string.charAt(0)||f.start!=c.ch-1))return e.Pass;var m,h="xml"!=g.mode.name&&"htmlmixed"==t.getMode().name;if(h&&"javascript"==g.mode.name)m=a+"script";else if(h&&"css"==g.mode.name)m=a+"style";else{var p=g.mode.xmlCurrentContext&&g.mode.xmlCurrentContext(u);if(!p||p.length&&i(t,p,p[p.length-1],c))return e.Pass;m=a+p[p.length-1]}">"!=t.getLine(c.line).charAt(f.end)&&(m+=">"),r[d]=m}if(t.replaceSelections(r),o=t.listSelections(),!s)for(d=0;d<o.length;d++)(d==o.length-1||o[d].head.line<o[d+1].head.line)&&t.indentLine(o[d].head.line)}function r(e,t){if(e.indexOf)return e.indexOf(t);for(var n=0,o=e.length;n<o;++n)if(e[n]==t)return n;return-1}function i(t,n,o,r,i){if(!e.scanForClosingTag)return!1;var a=Math.min(t.lastLine()+1,r.line+500),l=e.scanForClosingTag(t,r,null,a);if(!l||l.tag!=o)return!1;for(var s=i?1:0,d=n.length-1;d>=0&&n[d]==o;d--)++s;r=l.to;for(d=1;d<s;d++){var c=e.scanForClosingTag(t,r,null,a);if(!c||c.tag!=o)return!1;r=c.to}return!0}e.commands.closeTag=function(e){return o(e)}}));