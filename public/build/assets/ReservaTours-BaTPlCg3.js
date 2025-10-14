import{O as L,R as et,P as S,Q as G,T as y,U as at,V as $t,X as Z,Y as K,Z as rt,j as U,k as C,p as I,o as p,w as f,_ as F,n as st,$ as wt,a0 as kt,a1 as j,a2 as Tt,a3 as At,a4 as H,a5 as Bt,a6 as Q,c as g,f as w,b as s,A as E,a7 as Rt,a8 as nt,t as m,q as Ct,r as T,i as Pt,m as Nt,s as zt,y as Lt,a as c,u as v,g as St,J as Vt,K as Dt,a9 as q,B as X,h as x,H as Et,I as P,L as Y,aa as tt,E as D}from"./app-WKwrJkJC.js";import{_ as It}from"./AuthenticatedLayout-baeqBx4a.js";import"./logo-DN1bLr27.js";import"./index-Cz7jU3Cp.js";var Ft={root:function(t){var r=t.instance,o=t.props;return["p-tab",{"p-tab-active":r.active,"p-disabled":o.disabled}]}},_t=L.extend({name:"tab",classes:Ft}),Ot={name:"BaseTab",extends:S,props:{value:{type:[String,Number],default:void 0},disabled:{type:Boolean,default:!1},as:{type:[String,Object],default:"BUTTON"},asChild:{type:Boolean,default:!1}},style:_t,provide:function(){return{$pcTab:this,$parentInstance:this}}},ot={name:"Tab",extends:Ot,inheritAttrs:!1,inject:["$pcTabs","$pcTabList"],methods:{onFocus:function(){this.$pcTabs.selectOnFocus&&this.changeActiveValue()},onClick:function(){this.changeActiveValue()},onKeydown:function(t){switch(t.code){case"ArrowRight":this.onArrowRightKey(t);break;case"ArrowLeft":this.onArrowLeftKey(t);break;case"Home":this.onHomeKey(t);break;case"End":this.onEndKey(t);break;case"PageDown":this.onPageDownKey(t);break;case"PageUp":this.onPageUpKey(t);break;case"Enter":case"NumpadEnter":case"Space":this.onEnterKey(t);break}},onArrowRightKey:function(t){var r=this.findNextTab(t.currentTarget);r?this.changeFocusedTab(t,r):this.onHomeKey(t),t.preventDefault()},onArrowLeftKey:function(t){var r=this.findPrevTab(t.currentTarget);r?this.changeFocusedTab(t,r):this.onEndKey(t),t.preventDefault()},onHomeKey:function(t){var r=this.findFirstTab();this.changeFocusedTab(t,r),t.preventDefault()},onEndKey:function(t){var r=this.findLastTab();this.changeFocusedTab(t,r),t.preventDefault()},onPageDownKey:function(t){this.scrollInView(this.findLastTab()),t.preventDefault()},onPageUpKey:function(t){this.scrollInView(this.findFirstTab()),t.preventDefault()},onEnterKey:function(t){this.changeActiveValue(),t.preventDefault()},findNextTab:function(t){var r=arguments.length>1&&arguments[1]!==void 0?arguments[1]:!1,o=r?t:t.nextElementSibling;return o?K(o,"data-p-disabled")||K(o,"data-pc-section")==="activebar"?this.findNextTab(o):Z(o,'[data-pc-name="tab"]'):null},findPrevTab:function(t){var r=arguments.length>1&&arguments[1]!==void 0?arguments[1]:!1,o=r?t:t.previousElementSibling;return o?K(o,"data-p-disabled")||K(o,"data-pc-section")==="activebar"?this.findPrevTab(o):Z(o,'[data-pc-name="tab"]'):null},findFirstTab:function(){return this.findNextTab(this.$pcTabList.$refs.tabs.firstElementChild,!0)},findLastTab:function(){return this.findPrevTab(this.$pcTabList.$refs.tabs.lastElementChild,!0)},changeActiveValue:function(){this.$pcTabs.updateValue(this.value)},changeFocusedTab:function(t,r){$t(r),this.scrollInView(r)},scrollInView:function(t){var r;t==null||(r=t.scrollIntoView)===null||r===void 0||r.call(t,{block:"nearest"})}},computed:{active:function(){var t;return at((t=this.$pcTabs)===null||t===void 0?void 0:t.d_value,this.value)},id:function(){var t;return"".concat((t=this.$pcTabs)===null||t===void 0?void 0:t.$id,"_tab_").concat(this.value)},ariaControls:function(){var t;return"".concat((t=this.$pcTabs)===null||t===void 0?void 0:t.$id,"_tabpanel_").concat(this.value)},attrs:function(){return y(this.asAttrs,this.a11yAttrs,this.ptmi("root",this.ptParams))},asAttrs:function(){return this.as==="BUTTON"?{type:"button",disabled:this.disabled}:void 0},a11yAttrs:function(){return{id:this.id,tabindex:this.active?this.$pcTabs.tabindex:-1,role:"tab","aria-selected":this.active,"aria-controls":this.ariaControls,"data-pc-name":"tab","data-p-disabled":this.disabled,"data-p-active":this.active,onFocus:this.onFocus,onKeydown:this.onKeydown}},ptParams:function(){return{context:{active:this.active}}},dataP:function(){return G({active:this.active})}},directives:{ripple:et}};function Kt(e,t,r,o,i,n){var h=rt("ripple");return e.asChild?C(e.$slots,"default",{key:1,dataP:n.dataP,class:st(e.cx("root")),active:n.active,a11yAttrs:n.a11yAttrs,onClick:n.onClick}):U((p(),I(F(e.as),y({key:0,class:e.cx("root"),"data-p":n.dataP,onClick:n.onClick},n.attrs),{default:f(function(){return[C(e.$slots,"default")]}),_:3},16,["class","data-p","onClick"])),[[h]])}ot.render=Kt;var Ht={root:"p-tablist",content:function(t){var r=t.instance;return["p-tablist-content",{"p-tablist-viewport":r.$pcTabs.scrollable}]},tabList:"p-tablist-tab-list",activeBar:"p-tablist-active-bar",prevButton:"p-tablist-prev-button p-tablist-nav-button",nextButton:"p-tablist-next-button p-tablist-nav-button"},Ut=L.extend({name:"tablist",classes:Ht}),Mt={name:"BaseTabList",extends:S,props:{},style:Ut,provide:function(){return{$pcTabList:this,$parentInstance:this}}},it={name:"TabList",extends:Mt,inheritAttrs:!1,inject:["$pcTabs"],data:function(){return{isPrevButtonEnabled:!1,isNextButtonEnabled:!0}},resizeObserver:void 0,watch:{showNavigators:function(t){t?this.bindResizeObserver():this.unbindResizeObserver()},activeValue:{flush:"post",handler:function(){this.updateInkBar()}}},mounted:function(){var t=this;setTimeout(function(){t.updateInkBar()},150),this.showNavigators&&(this.updateButtonState(),this.bindResizeObserver())},updated:function(){this.showNavigators&&this.updateButtonState()},beforeUnmount:function(){this.unbindResizeObserver()},methods:{onScroll:function(t){this.showNavigators&&this.updateButtonState(),t.preventDefault()},onPrevButtonClick:function(){var t=this.$refs.content,r=this.getVisibleButtonWidths(),o=j(t)-r,i=Math.abs(t.scrollLeft),n=o*.8,h=i-n,b=Math.max(h,0);t.scrollLeft=Q(t)?-1*b:b},onNextButtonClick:function(){var t=this.$refs.content,r=this.getVisibleButtonWidths(),o=j(t)-r,i=Math.abs(t.scrollLeft),n=o*.8,h=i+n,b=t.scrollWidth-o,$=Math.min(h,b);t.scrollLeft=Q(t)?-1*$:$},bindResizeObserver:function(){var t=this;this.resizeObserver=new ResizeObserver(function(){return t.updateButtonState()}),this.resizeObserver.observe(this.$refs.list)},unbindResizeObserver:function(){var t;(t=this.resizeObserver)===null||t===void 0||t.unobserve(this.$refs.list),this.resizeObserver=void 0},updateInkBar:function(){var t=this.$refs,r=t.content,o=t.inkbar,i=t.tabs;if(o){var n=Z(r,'[data-pc-name="tab"][data-p-active="true"]');this.$pcTabs.isVertical()?(o.style.height=At(n)+"px",o.style.top=H(n).top-H(i).top+"px"):(o.style.width=Bt(n)+"px",o.style.left=H(n).left-H(i).left+"px")}},updateButtonState:function(){var t=this.$refs,r=t.list,o=t.content,i=o.scrollTop,n=o.scrollWidth,h=o.scrollHeight,b=o.offsetWidth,$=o.offsetHeight,k=Math.abs(o.scrollLeft),A=[j(o),Tt(o)],V=A[0],N=A[1];this.$pcTabs.isVertical()?(this.isPrevButtonEnabled=i!==0,this.isNextButtonEnabled=r.offsetHeight>=$&&parseInt(i)!==h-N):(this.isPrevButtonEnabled=k!==0,this.isNextButtonEnabled=r.offsetWidth>=b&&parseInt(k)!==n-V)},getVisibleButtonWidths:function(){var t=this.$refs,r=t.prevButton,o=t.nextButton,i=0;return this.showNavigators&&(i=((r==null?void 0:r.offsetWidth)||0)+((o==null?void 0:o.offsetWidth)||0)),i}},computed:{templates:function(){return this.$pcTabs.$slots},activeValue:function(){return this.$pcTabs.d_value},showNavigators:function(){return this.$pcTabs.scrollable&&this.$pcTabs.showNavigators},prevButtonAriaLabel:function(){return this.$primevue.config.locale.aria?this.$primevue.config.locale.aria.previous:void 0},nextButtonAriaLabel:function(){return this.$primevue.config.locale.aria?this.$primevue.config.locale.aria.next:void 0},dataP:function(){return G({scrollable:this.$pcTabs.scrollable})}},components:{ChevronLeftIcon:kt,ChevronRightIcon:wt},directives:{ripple:et}},Wt=["data-p"],jt=["aria-label","tabindex"],qt=["data-p"],Zt=["aria-orientation"],Gt=["aria-label","tabindex"];function Jt(e,t,r,o,i,n){var h=rt("ripple");return p(),g("div",y({ref:"list",class:e.cx("root"),"data-p":n.dataP},e.ptmi("root")),[n.showNavigators&&i.isPrevButtonEnabled?U((p(),g("button",y({key:0,ref:"prevButton",type:"button",class:e.cx("prevButton"),"aria-label":n.prevButtonAriaLabel,tabindex:n.$pcTabs.tabindex,onClick:t[0]||(t[0]=function(){return n.onPrevButtonClick&&n.onPrevButtonClick.apply(n,arguments)})},e.ptm("prevButton"),{"data-pc-group-section":"navigator"}),[(p(),I(F(n.templates.previcon||"ChevronLeftIcon"),y({"aria-hidden":"true"},e.ptm("prevIcon")),null,16))],16,jt)),[[h]]):w("",!0),s("div",y({ref:"content",class:e.cx("content"),onScroll:t[1]||(t[1]=function(){return n.onScroll&&n.onScroll.apply(n,arguments)}),"data-p":n.dataP},e.ptm("content")),[s("div",y({ref:"tabs",class:e.cx("tabList"),role:"tablist","aria-orientation":n.$pcTabs.orientation||"horizontal"},e.ptm("tabList")),[C(e.$slots,"default"),s("span",y({ref:"inkbar",class:e.cx("activeBar"),role:"presentation","aria-hidden":"true"},e.ptm("activeBar")),null,16)],16,Zt)],16,qt),n.showNavigators&&i.isNextButtonEnabled?U((p(),g("button",y({key:1,ref:"nextButton",type:"button",class:e.cx("nextButton"),"aria-label":n.nextButtonAriaLabel,tabindex:n.$pcTabs.tabindex,onClick:t[2]||(t[2]=function(){return n.onNextButtonClick&&n.onNextButtonClick.apply(n,arguments)})},e.ptm("nextButton"),{"data-pc-group-section":"navigator"}),[(p(),I(F(n.templates.nexticon||"ChevronRightIcon"),y({"aria-hidden":"true"},e.ptm("nextIcon")),null,16))],16,Gt)),[[h]]):w("",!0)],16,Wt)}it.render=Jt;var Qt={root:function(t){var r=t.instance;return["p-tabpanel",{"p-tabpanel-active":r.active}]}},Xt=L.extend({name:"tabpanel",classes:Qt}),Yt={name:"BaseTabPanel",extends:S,props:{value:{type:[String,Number],default:void 0},as:{type:[String,Object],default:"DIV"},asChild:{type:Boolean,default:!1},header:null,headerStyle:null,headerClass:null,headerProps:null,headerActionProps:null,contentStyle:null,contentClass:null,contentProps:null,disabled:Boolean},style:Xt,provide:function(){return{$pcTabPanel:this,$parentInstance:this}}},lt={name:"TabPanel",extends:Yt,inheritAttrs:!1,inject:["$pcTabs"],computed:{active:function(){var t;return at((t=this.$pcTabs)===null||t===void 0?void 0:t.d_value,this.value)},id:function(){var t;return"".concat((t=this.$pcTabs)===null||t===void 0?void 0:t.$id,"_tabpanel_").concat(this.value)},ariaLabelledby:function(){var t;return"".concat((t=this.$pcTabs)===null||t===void 0?void 0:t.$id,"_tab_").concat(this.value)},attrs:function(){return y(this.a11yAttrs,this.ptmi("root",this.ptParams))},a11yAttrs:function(){var t;return{id:this.id,tabindex:(t=this.$pcTabs)===null||t===void 0?void 0:t.tabindex,role:"tabpanel","aria-labelledby":this.ariaLabelledby,"data-pc-name":"tabpanel","data-p-active":this.active}},ptParams:function(){return{context:{active:this.active}}}}};function te(e,t,r,o,i,n){var h,b;return n.$pcTabs?(p(),g(E,{key:1},[e.asChild?C(e.$slots,"default",{key:1,class:st(e.cx("root")),active:n.active,a11yAttrs:n.a11yAttrs}):(p(),g(E,{key:0},[!((h=n.$pcTabs)!==null&&h!==void 0&&h.lazy)||n.active?U((p(),I(F(e.as),y({key:0,class:e.cx("root")},n.attrs),{default:f(function(){return[C(e.$slots,"default")]}),_:3},16,["class"])),[[Rt,(b=n.$pcTabs)!==null&&b!==void 0&&b.lazy?!0:n.active]]):w("",!0)],64))],64)):C(e.$slots,"default",{key:0})}lt.render=te;var ee={root:"p-tabpanels"},ae=L.extend({name:"tabpanels",classes:ee}),re={name:"BaseTabPanels",extends:S,props:{},style:ae,provide:function(){return{$pcTabPanels:this,$parentInstance:this}}},dt={name:"TabPanels",extends:re,inheritAttrs:!1};function se(e,t,r,o,i,n){return p(),g("div",y({class:e.cx("root"),role:"presentation"},e.ptmi("root")),[C(e.$slots,"default")],16)}dt.render=se;var ne=nt`
    .p-tabs {
        display: flex;
        flex-direction: column;
    }

    .p-tablist {
        display: flex;
        position: relative;
    }

    .p-tabs-scrollable > .p-tablist {
        overflow: hidden;
    }

    .p-tablist-viewport {
        overflow-x: auto;
        overflow-y: hidden;
        scroll-behavior: smooth;
        scrollbar-width: none;
        overscroll-behavior: contain auto;
    }

    .p-tablist-viewport::-webkit-scrollbar {
        display: none;
    }

    .p-tablist-tab-list {
        position: relative;
        display: flex;
        background: dt('tabs.tablist.background');
        border-style: solid;
        border-color: dt('tabs.tablist.border.color');
        border-width: dt('tabs.tablist.border.width');
    }

    .p-tablist-content {
        flex-grow: 1;
    }

    .p-tablist-nav-button {
        all: unset;
        position: absolute !important;
        flex-shrink: 0;
        inset-block-start: 0;
        z-index: 2;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: dt('tabs.nav.button.background');
        color: dt('tabs.nav.button.color');
        width: dt('tabs.nav.button.width');
        transition:
            color dt('tabs.transition.duration'),
            outline-color dt('tabs.transition.duration'),
            box-shadow dt('tabs.transition.duration');
        box-shadow: dt('tabs.nav.button.shadow');
        outline-color: transparent;
        cursor: pointer;
    }

    .p-tablist-nav-button:focus-visible {
        z-index: 1;
        box-shadow: dt('tabs.nav.button.focus.ring.shadow');
        outline: dt('tabs.nav.button.focus.ring.width') dt('tabs.nav.button.focus.ring.style') dt('tabs.nav.button.focus.ring.color');
        outline-offset: dt('tabs.nav.button.focus.ring.offset');
    }

    .p-tablist-nav-button:hover {
        color: dt('tabs.nav.button.hover.color');
    }

    .p-tablist-prev-button {
        inset-inline-start: 0;
    }

    .p-tablist-next-button {
        inset-inline-end: 0;
    }

    .p-tablist-prev-button:dir(rtl),
    .p-tablist-next-button:dir(rtl) {
        transform: rotate(180deg);
    }

    .p-tab {
        flex-shrink: 0;
        cursor: pointer;
        user-select: none;
        position: relative;
        border-style: solid;
        white-space: nowrap;
        gap: dt('tabs.tab.gap');
        background: dt('tabs.tab.background');
        border-width: dt('tabs.tab.border.width');
        border-color: dt('tabs.tab.border.color');
        color: dt('tabs.tab.color');
        padding: dt('tabs.tab.padding');
        font-weight: dt('tabs.tab.font.weight');
        transition:
            background dt('tabs.transition.duration'),
            border-color dt('tabs.transition.duration'),
            color dt('tabs.transition.duration'),
            outline-color dt('tabs.transition.duration'),
            box-shadow dt('tabs.transition.duration');
        margin: dt('tabs.tab.margin');
        outline-color: transparent;
    }

    .p-tab:not(.p-disabled):focus-visible {
        z-index: 1;
        box-shadow: dt('tabs.tab.focus.ring.shadow');
        outline: dt('tabs.tab.focus.ring.width') dt('tabs.tab.focus.ring.style') dt('tabs.tab.focus.ring.color');
        outline-offset: dt('tabs.tab.focus.ring.offset');
    }

    .p-tab:not(.p-tab-active):not(.p-disabled):hover {
        background: dt('tabs.tab.hover.background');
        border-color: dt('tabs.tab.hover.border.color');
        color: dt('tabs.tab.hover.color');
    }

    .p-tab-active {
        background: dt('tabs.tab.active.background');
        border-color: dt('tabs.tab.active.border.color');
        color: dt('tabs.tab.active.color');
    }

    .p-tabpanels {
        background: dt('tabs.tabpanel.background');
        color: dt('tabs.tabpanel.color');
        padding: dt('tabs.tabpanel.padding');
        outline: 0 none;
    }

    .p-tabpanel:focus-visible {
        box-shadow: dt('tabs.tabpanel.focus.ring.shadow');
        outline: dt('tabs.tabpanel.focus.ring.width') dt('tabs.tabpanel.focus.ring.style') dt('tabs.tabpanel.focus.ring.color');
        outline-offset: dt('tabs.tabpanel.focus.ring.offset');
    }

    .p-tablist-active-bar {
        z-index: 1;
        display: block;
        position: absolute;
        inset-block-end: dt('tabs.active.bar.bottom');
        height: dt('tabs.active.bar.height');
        background: dt('tabs.active.bar.background');
        transition: 250ms cubic-bezier(0.35, 0, 0.25, 1);
    }
`,oe={root:function(t){var r=t.props;return["p-tabs p-component",{"p-tabs-scrollable":r.scrollable}]}},ie=L.extend({name:"tabs",style:ne,classes:oe}),le={name:"BaseTabs",extends:S,props:{value:{type:[String,Number],default:void 0},lazy:{type:Boolean,default:!1},scrollable:{type:Boolean,default:!1},showNavigators:{type:Boolean,default:!0},tabindex:{type:Number,default:0},selectOnFocus:{type:Boolean,default:!1}},style:ie,provide:function(){return{$pcTabs:this,$parentInstance:this}}},ut={name:"Tabs",extends:le,inheritAttrs:!1,emits:["update:value"],data:function(){return{d_value:this.value}},watch:{value:function(t){this.d_value=t}},methods:{updateValue:function(t){this.d_value!==t&&(this.d_value=t,this.$emit("update:value",t))},isVertical:function(){return this.orientation==="vertical"}}};function de(e,t,r,o,i,n){return p(),g("div",y({class:e.cx("root")},e.ptmi("root")),[C(e.$slots,"default")],16)}ut.render=de;var ue=nt`
    .p-tag {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: dt('tag.primary.background');
        color: dt('tag.primary.color');
        font-size: dt('tag.font.size');
        font-weight: dt('tag.font.weight');
        padding: dt('tag.padding');
        border-radius: dt('tag.border.radius');
        gap: dt('tag.gap');
    }

    .p-tag-icon {
        font-size: dt('tag.icon.size');
        width: dt('tag.icon.size');
        height: dt('tag.icon.size');
    }

    .p-tag-rounded {
        border-radius: dt('tag.rounded.border.radius');
    }

    .p-tag-success {
        background: dt('tag.success.background');
        color: dt('tag.success.color');
    }

    .p-tag-info {
        background: dt('tag.info.background');
        color: dt('tag.info.color');
    }

    .p-tag-warn {
        background: dt('tag.warn.background');
        color: dt('tag.warn.color');
    }

    .p-tag-danger {
        background: dt('tag.danger.background');
        color: dt('tag.danger.color');
    }

    .p-tag-secondary {
        background: dt('tag.secondary.background');
        color: dt('tag.secondary.color');
    }

    .p-tag-contrast {
        background: dt('tag.contrast.background');
        color: dt('tag.contrast.color');
    }
`,ce={root:function(t){var r=t.props;return["p-tag p-component",{"p-tag-info":r.severity==="info","p-tag-success":r.severity==="success","p-tag-warn":r.severity==="warn","p-tag-danger":r.severity==="danger","p-tag-secondary":r.severity==="secondary","p-tag-contrast":r.severity==="contrast","p-tag-rounded":r.rounded}]},icon:"p-tag-icon",label:"p-tag-label"},be=L.extend({name:"tag",style:ue,classes:ce}),ve={name:"BaseTag",extends:S,props:{value:null,severity:null,rounded:Boolean,icon:String},style:be,provide:function(){return{$pcTag:this,$parentInstance:this}}};function _(e){"@babel/helpers - typeof";return _=typeof Symbol=="function"&&typeof Symbol.iterator=="symbol"?function(t){return typeof t}:function(t){return t&&typeof Symbol=="function"&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},_(e)}function pe(e,t,r){return(t=fe(t))in e?Object.defineProperty(e,t,{value:r,enumerable:!0,configurable:!0,writable:!0}):e[t]=r,e}function fe(e){var t=he(e,"string");return _(t)=="symbol"?t:t+""}function he(e,t){if(_(e)!="object"||!e)return e;var r=e[Symbol.toPrimitive];if(r!==void 0){var o=r.call(e,t);if(_(o)!="object")return o;throw new TypeError("@@toPrimitive must return a primitive value.")}return(t==="string"?String:Number)(e)}var ct={name:"Tag",extends:ve,inheritAttrs:!1,computed:{dataP:function(){return G(pe({rounded:this.rounded},this.severity,this.severity))}}},me=["data-p"];function ge(e,t,r,o,i,n){return p(),g("span",y({class:e.cx("root"),"data-p":n.dataP},e.ptmi("root")),[e.$slots.icon?(p(),I(F(e.$slots.icon),y({key:0,class:e.cx("icon")},e.ptm("icon")),null,16,["class"])):e.icon?(p(),g("span",y({key:1,class:[e.cx("icon"),e.icon]},e.ptm("icon")),null,16)):w("",!0),e.value!=null||e.$slots.default?C(e.$slots,"default",{key:2},function(){return[s("span",y({class:e.cx("label")},e.ptm("label")),m(e.value),17)]}):w("",!0)],16,me)}ct.render=ge;const ye={class:"py-4 sm:py-6 px-4 sm:px-7 mt-6 sm:mt-10 mx-auto bg-red-50 shadow-md rounded-lg"},xe={class:"bg-white p-4 rounded-lg shadow border border-gray-200 mb-6"},$e={class:"grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4"},we={class:"flex gap-2"},ke={class:"text-center py-8"},Te={class:"text-gray-500"},Ae={class:"font-medium"},Be={class:"text-sm text-gray-500"},Re={class:"font-medium"},Ce={class:"text-sm text-gray-500"},Pe={class:"text-center"},Ne={class:"text-sm"},ze={class:"font-medium"},Le={class:"text-sm text-gray-500"},Se={class:"font-medium"},Ve={class:"text-right font-medium text-green-600"},De={class:"flex gap-1 flex-wrap"},Ee=["onClick"],Ie=["onClick"],Fe=["onClick"],_e=["onClick"],Oe={class:"space-y-4"},Ke={key:0,class:"bg-gray-50 p-4 rounded-lg"},He={class:"flex justify-end gap-2"},Ue=["disabled"],Me={class:"space-y-4"},We={key:0,class:"bg-gray-50 p-4 rounded-lg"},je={class:"flex justify-end gap-2"},qe=["disabled"],Xe={__name:"ReservaTours",setup(e){const t=Ct(),r=T([]),o=T(!1),i=T({tipo:"tours",busqueda:"",fechaDesde:null,fechaHasta:null}),n=T(!1),h=T(!1),b=T(null),$=T(""),k=T(null),A=T(""),V=T(0),N=[{label:"Pendientes",value:"PENDIENTE",severity:"warn"},{label:"Confirmadas",value:"CONFIRMADA",severity:"success"},{label:"Rechazadas",value:"RECHAZADA",severity:"danger"},{label:"Reprogramadas",value:"REPROGRAMADA",severity:"info"},{label:"Finalizadas",value:"FINALIZADA",severity:"secondary"}],bt=Pt(()=>{var a;const l=(a=N[V.value])==null?void 0:a.value;return r.value.filter(B=>B.estado===l)}),M=async()=>{o.value=!0;try{const l={tipo:i.value.tipo,busqueda:i.value.busqueda||void 0,fecha_inicio:i.value.fechaDesde||void 0,fecha_fin:i.value.fechaHasta||void 0},a=await D.get("/api/reservas",{params:l});console.log("Datos recibidos:",a.data),r.value=a.data.data||[]}catch(l){console.error("Error al cargar reservas:",l),t.add({severity:"error",summary:"Error",detail:"No se pudieron cargar las reservas",life:3e3}),r.value=[]}finally{o.value=!1}},vt=async l=>{try{await D.put(`/api/reservas/${l.id}/confirmar`);const a=r.value.findIndex(B=>B.id===l.id);a!==-1&&(r.value[a].estado="CONFIRMADA"),t.add({severity:"success",summary:"Éxito",detail:"Reserva confirmada correctamente",life:3e3})}catch(a){console.error("Error al confirmar reserva:",a),t.add({severity:"error",summary:"Error",detail:"No se pudo confirmar la reserva",life:3e3})}},pt=l=>{b.value=l,$.value="",h.value=!0},ft=async()=>{if(!$.value.trim()){t.add({severity:"warn",summary:"Advertencia",detail:"Debe ingresar un motivo para rechazar la reserva",life:3e3});return}try{await D.put(`/api/reservas/${b.value.id}/rechazar`,{motivo:$.value});const l=r.value.findIndex(a=>a.id===b.value.id);l!==-1&&(r.value[l].estado="RECHAZADA"),h.value=!1,t.add({severity:"success",summary:"Éxito",detail:"Reserva rechazada correctamente",life:3e3})}catch(l){console.error("Error al rechazar reserva:",l),t.add({severity:"error",summary:"Error",detail:"No se pudo rechazar la reserva",life:3e3})}},ht=l=>{b.value=l,k.value=null,A.value="",n.value=!0},mt=async()=>{if(!k.value||!A.value.trim()){t.add({severity:"warn",summary:"Advertencia",detail:"Debe ingresar una nueva fecha y motivo para reprogramar",life:3e3});return}try{await D.put(`/api/reservas/${b.value.id}/reprogramar`,{fecha_nueva:k.value,motivo:A.value});const l=r.value.findIndex(a=>a.id===b.value.id);l!==-1&&(r.value[l].estado="REPROGRAMADA",r.value[l].fecha_reserva=k.value),n.value=!1,t.add({severity:"success",summary:"Éxito",detail:"Reserva reprogramada correctamente",life:3e3})}catch(l){console.error("Error al reprogramar reserva:",l),t.add({severity:"error",summary:"Error",detail:"No se pudo reprogramar la reserva",life:3e3})}},gt=async l=>{try{await D.put(`/api/reservas/${l.id}/confirmar`);const a=r.value.findIndex(B=>B.id===l.id);a!==-1&&(r.value[a].estado="FINALIZADA"),t.add({severity:"success",summary:"Éxito",detail:"Reserva finalizada correctamente",life:3e3})}catch(a){console.error("Error al finalizar reserva:",a),t.add({severity:"error",summary:"Error",detail:"No se pudo finalizar la reserva",life:3e3})}},O=l=>{switch(l.estado){case"PENDIENTE":return["confirmar","rechazar","reprogramar"];case"CONFIRMADA":return["rechazar","reprogramar","finalizar"];case"REPROGRAMADA":return["confirmar","rechazar"];case"RECHAZADA":return[];case"FINALIZADA":return[];default:return[]}},yt=l=>{const a=N.find(B=>B.value===l);return(a==null?void 0:a.severity)||"secondary"},W=l=>l?new Date(l).toLocaleDateString("es-ES"):"N/A",xt=()=>{i.value.fechaDesde=null,i.value.fechaHasta=null,M()};return Nt(()=>i.value,()=>{M()},{deep:!0}),zt(()=>{M()}),(l,a)=>{const B=Lt("Toast");return p(),g(E,null,[c(v(St),{title:"Gestión de Reservas"}),c(It,null,{default:f(()=>[c(B,{class:"z-[9999]"}),s("div",ye,[a[36]||(a[36]=s("div",{class:"flex flex-col lg:flex-row lg:justify-between lg:items-center mb-6 gap-4"},[s("h3",{class:"text-lg sm:text-2xl text-blue-600 font-bold"},"Gestión de Reservas")],-1)),s("div",xe,[s("div",$e,[s("div",null,[a[12]||(a[12]=s("label",{class:"block text-sm font-medium text-gray-700 mb-2"},"Tipo de Reserva",-1)),c(v(Vt),{modelValue:i.value.tipo,"onUpdate:modelValue":a[0]||(a[0]=d=>i.value.tipo=d),options:[{label:"Tours",value:"tours"},{label:"Hoteles",value:"hoteles"},{label:"Aerolíneas",value:"aerolineas"}],optionLabel:"label",optionValue:"value",class:"w-full"},null,8,["modelValue"])]),s("div",null,[a[13]||(a[13]=s("label",{class:"block text-sm font-medium text-gray-700 mb-2"},"Buscar",-1)),c(v(Dt),{modelValue:i.value.busqueda,"onUpdate:modelValue":a[1]||(a[1]=d=>i.value.busqueda=d),placeholder:"Buscar por cliente, tour, hotel...",class:"w-full"},null,8,["modelValue"])]),s("div",null,[a[14]||(a[14]=s("label",{class:"block text-sm font-medium text-gray-700 mb-2"},"Desde",-1)),c(v(q),{modelValue:i.value.fechaDesde,"onUpdate:modelValue":a[2]||(a[2]=d=>i.value.fechaDesde=d),dateFormat:"dd/mm/yy",class:"w-full",showIcon:"",placeholder:"Fecha inicio"},null,8,["modelValue"])]),s("div",null,[a[16]||(a[16]=s("label",{class:"block text-sm font-medium text-gray-700 mb-2"},"Hasta",-1)),s("div",we,[c(v(q),{modelValue:i.value.fechaHasta,"onUpdate:modelValue":a[3]||(a[3]=d=>i.value.fechaHasta=d),dateFormat:"dd/mm/yy",class:"flex-1",showIcon:"",placeholder:"Fecha fin"},null,8,["modelValue"]),s("button",{class:"bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded-md transition-colors",onClick:xt,title:"Limpiar fechas"},a[15]||(a[15]=[s("i",{class:"pi pi-times"},null,-1)]))])])])]),c(v(ut),{value:V.value,"onUpdate:value":a[4]||(a[4]=d=>V.value=d),class:"bg-white rounded-lg shadow"},{default:f(()=>[c(v(it),null,{default:f(()=>[(p(),g(E,null,X(N,(d,R)=>c(v(ot),{key:d.value,value:R},{default:f(()=>[x(m(d.label)+" ("+m(r.value.filter(u=>u.estado===d.value).length)+") ",1)]),_:2},1032,["value"])),64))]),_:1}),c(v(dt),null,{default:f(()=>[(p(),g(E,null,X(N,(d,R)=>c(v(lt),{key:d.value,value:R},{default:f(()=>[c(v(Et),{value:bt.value,loading:o.value,paginator:"",rows:10,rowsPerPageOptions:[5,10,20],paginatorTemplate:"FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown",currentPageReportTemplate:"Mostrando {first} a {last} de {totalRecords} reservas",responsiveLayout:"scroll",class:"mt-4"},{empty:f(()=>[s("div",ke,[a[17]||(a[17]=s("i",{class:"pi pi-inbox text-4xl text-gray-400 mb-4"},null,-1)),s("p",Te,"No hay reservas "+m(d.label.toLowerCase()),1)])]),loading:f(()=>a[18]||(a[18]=[s("div",{class:"text-center py-8"},[s("i",{class:"pi pi-spin pi-spinner text-2xl text-blue-500"}),s("p",{class:"text-gray-500 mt-2"},"Cargando reservas...")],-1)])),default:f(()=>[c(v(P),{field:"fecha_reserva",header:"Fecha",sortable:"",class:"min-w-[100px]"},{body:f(u=>[x(m(W(u.data.fecha_reserva)),1)]),_:1}),c(v(P),{field:"cliente.nombres",header:"Cliente",sortable:"",class:"min-w-[150px]"},{body:f(u=>{var z,J;return[s("div",null,[s("div",Ae,m(((z=u.data.cliente)==null?void 0:z.nombres)||"N/A"),1),s("div",Be,m(((J=u.data.cliente)==null?void 0:J.correo)||"N/A"),1)])]}),_:1}),c(v(P),{field:"entidad_nombre",header:"Servicio",sortable:"",class:"min-w-[200px]"},{body:f(u=>[s("div",null,[s("div",Re,m(u.data.entidad_nombre||"N/A"),1),s("div",Ce,m(u.data.tipo||"N/A"),1)])]),_:1}),c(v(P),{header:"Personas",class:"min-w-[100px]"},{body:f(u=>[s("div",Pe,[s("div",Ne,[s("span",ze,m(u.data.mayores_edad||0),1),a[19]||(a[19]=x(" adultos "))]),s("div",Le,[s("span",Se,m(u.data.menores_edad||0),1),a[20]||(a[20]=x(" niños "))])])]),_:1}),c(v(P),{field:"total",header:"Total",sortable:"",class:"min-w-[100px]"},{body:f(u=>[s("div",Ve," $"+m(Number(u.data.total||0).toFixed(2)),1)]),_:1}),c(v(P),{field:"estado",header:"Estado",class:"min-w-[120px]"},{body:f(u=>[c(v(ct),{value:u.data.estado,severity:yt(u.data.estado),class:"text-sm"},null,8,["value","severity"])]),_:2},1024),c(v(P),{header:"Acciones",class:"min-w-[200px]"},{body:f(u=>[s("div",De,[O(u.data).includes("confirmar")?(p(),g("button",{key:0,class:"bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-sm transition-colors",onClick:z=>vt(u.data),title:"Confirmar reserva"},a[21]||(a[21]=[s("i",{class:"pi pi-check mr-1"},null,-1),x(" Confirmar ")]),8,Ee)):w("",!0),O(u.data).includes("rechazar")?(p(),g("button",{key:1,class:"bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm transition-colors",onClick:z=>pt(u.data),title:"Rechazar reserva"},a[22]||(a[22]=[s("i",{class:"pi pi-times mr-1"},null,-1),x(" Rechazar ")]),8,Ie)):w("",!0),O(u.data).includes("reprogramar")?(p(),g("button",{key:2,class:"bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm transition-colors",onClick:z=>ht(u.data),title:"Reprogramar reserva"},a[23]||(a[23]=[s("i",{class:"pi pi-calendar mr-1"},null,-1),x(" Reprogramar ")]),8,Fe)):w("",!0),O(u.data).includes("finalizar")?(p(),g("button",{key:3,class:"bg-purple-500 hover:bg-purple-600 text-white px-3 py-1 rounded text-sm transition-colors",onClick:z=>gt(u.data),title:"Finalizar reserva"},a[24]||(a[24]=[s("i",{class:"pi pi-check-circle mr-1"},null,-1),x(" Finalizar ")]),8,_e)):w("",!0)])]),_:1})]),_:2},1032,["value","loading"])]),_:2},1032,["value"])),64))]),_:1})]),_:1},8,["value"]),c(v(Y),{visible:h.value,"onUpdate:visible":a[7]||(a[7]=d=>h.value=d),modal:"",header:"Rechazar Reserva",style:{width:"500px"}},{footer:f(()=>[s("div",He,[s("button",{class:"bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded transition-colors",onClick:a[6]||(a[6]=d=>h.value=!1)}," Cancelar "),s("button",{class:"bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded transition-colors",onClick:ft,disabled:!$.value.trim()}," Rechazar Reserva ",8,Ue)])]),default:f(()=>{var d;return[s("div",Oe,[b.value?(p(),g("div",Ke,[a[28]||(a[28]=s("h4",{class:"font-medium text-gray-800 mb-2"},"Detalles de la Reserva",-1)),s("p",null,[a[25]||(a[25]=s("strong",null,"Cliente:",-1)),x(" "+m((d=b.value.cliente)==null?void 0:d.nombres),1)]),s("p",null,[a[26]||(a[26]=s("strong",null,"Servicio:",-1)),x(" "+m(b.value.entidad_nombre),1)]),s("p",null,[a[27]||(a[27]=s("strong",null,"Fecha:",-1)),x(" "+m(W(b.value.fecha_reserva)),1)])])):w("",!0),s("div",null,[a[29]||(a[29]=s("label",{class:"block text-sm font-medium text-gray-700 mb-2"},[x(" Motivo del rechazo "),s("span",{class:"text-red-500"},"*")],-1)),c(v(tt),{modelValue:$.value,"onUpdate:modelValue":a[5]||(a[5]=R=>$.value=R),placeholder:"Ingrese el motivo por el cual se rechaza esta reserva...",rows:"3",class:"w-full"},null,8,["modelValue"])])])]}),_:1},8,["visible"]),c(v(Y),{visible:n.value,"onUpdate:visible":a[11]||(a[11]=d=>n.value=d),modal:"",header:"Reprogramar Reserva",style:{width:"500px"}},{footer:f(()=>[s("div",je,[s("button",{class:"bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded transition-colors",onClick:a[10]||(a[10]=d=>n.value=!1)}," Cancelar "),s("button",{class:"bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded transition-colors",onClick:mt,disabled:!k.value||!A.value.trim()}," Reprogramar ",8,qe)])]),default:f(()=>{var d;return[s("div",Me,[b.value?(p(),g("div",We,[a[33]||(a[33]=s("h4",{class:"font-medium text-gray-800 mb-2"},"Detalles de la Reserva",-1)),s("p",null,[a[30]||(a[30]=s("strong",null,"Cliente:",-1)),x(" "+m((d=b.value.cliente)==null?void 0:d.nombres),1)]),s("p",null,[a[31]||(a[31]=s("strong",null,"Servicio:",-1)),x(" "+m(b.value.entidad_nombre),1)]),s("p",null,[a[32]||(a[32]=s("strong",null,"Fecha actual:",-1)),x(" "+m(W(b.value.fecha_reserva)),1)])])):w("",!0),s("div",null,[a[34]||(a[34]=s("label",{class:"block text-sm font-medium text-gray-700 mb-2"},[x(" Nueva fecha "),s("span",{class:"text-red-500"},"*")],-1)),c(v(q),{modelValue:k.value,"onUpdate:modelValue":a[8]||(a[8]=R=>k.value=R),dateFormat:"dd/mm/yy",class:"w-full",showIcon:"",placeholder:"Seleccione la nueva fecha",minDate:new Date},null,8,["modelValue","minDate"])]),s("div",null,[a[35]||(a[35]=s("label",{class:"block text-sm font-medium text-gray-700 mb-2"},[x(" Motivo de la reprogramación "),s("span",{class:"text-red-500"},"*")],-1)),c(v(tt),{modelValue:A.value,"onUpdate:modelValue":a[9]||(a[9]=R=>A.value=R),placeholder:"Ingrese el motivo de la reprogramación...",rows:"3",class:"w-full"},null,8,["modelValue"])])])]}),_:1},8,["visible"])])]),_:1})],64)}}};export{Xe as default};
