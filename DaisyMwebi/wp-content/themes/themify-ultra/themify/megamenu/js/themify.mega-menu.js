/*
 * Themify Mega Menu Plugin
 */

( (Themify, doc)=> {
	'use strict';
	let maxW;
	const cacheMenu =new Map(),
		ev = Themify.isTouch?'click':'mouseover',
		init = async function(e){
			if (Themify.w < maxW || e.target.closest('.mega-menu-posts')) {
				return;
			}
			let el=e.type==='mouseenter' || e.type==='focus'?this.tfClass('mega-link')[0]:e.target;
			if(el.classList.contains('child-arrow')){
				el=el.closest('.has-mega-sub-menu').tfClass('mega-link')[0];
			}
			el = el.classList.contains('mega-link')?el:el.closest('.tf_mega_taxes .mega-link');
			if(!el){
				return;
			}
			if(e.type==='click'){
				e.preventDefault();
			}
			const termid = el.dataset.termid,
				tax = el.dataset.tax,
				cl=el.tfTag('a')[0].classList,
				wrapper=el.closest('.mega-sub-menu'),
				selected=wrapper.tfClass('tf_mega_selected')[0];

			let megaMenuPosts = el.tfClass('mega-menu-posts')[0],
				response=cacheMenu.get(termid);

			if(!megaMenuPosts){
				megaMenuPosts = doc.createElement('div');
				megaMenuPosts.className = 'mega-menu-posts tf_left tf_box';
				el.appendChild(megaMenuPosts);
			}
			if(selected){
				selected.classList.remove('tf_mega_selected');
			}
			el.classList.add('tf_mega_selected');
			if (response=== undefined) {
				if(cl.contains('tf_loader')){
					return;
				}
				cl.add('tf_loader');
				try{
					response=await Themify.fetch({
						action: 'themify_theme_mega_posts',
						termid: termid,
						tax: tax
					},'text');
					cacheMenu.set(termid,response);
				}
				catch(e){

				}
				cl.remove('tf_loader');
			}
			if (response!== undefined) {
				megaMenuPosts.innerHTML=response;
			}
		};

	Themify.on('tf_mega_menu',  (menu,mob_point)=> {
		const items=menu.tfClass('tf_mega_taxes');
		maxW=mob_point;
		for(let i=items.length-1;i>-1;--i){
			items[i].tfOn(ev,init);
			if(ev==='mouseover'){
				items[i].tfOn('focusin',init,{passive:true});
			}
			let parent=items[i].closest('.has-mega-sub-menu');
			if(parent){
				if(ev==='mouseover'){
					parent.tfOn('mouseenter',init,{passive:true});
					parent.querySelector('a').tfOn('focus',init.bind(parent),{passive:true});
				}else{
					parent.tfClass('child-arrow')[0].tfOn('click',init);
				}
			}
		}
	},true);
	if(!Themify.isTouch){
		setTimeout(()=>{
			Themify.edgeMenu();
		},1500);
	}
})(Themify, document);
