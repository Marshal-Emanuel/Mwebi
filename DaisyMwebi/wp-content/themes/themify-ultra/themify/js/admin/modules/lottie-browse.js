let TF_LottieBrowse;
((doc, Themify,und)=> {
    'use strict';
    let observer,
        jsonData,
        holders=new WeakMap(),
        timer;
    const initObserver=root=>{
        if(!observer){
            observer=new IntersectionObserver((entries, _self)=>{
                for (let i = entries.length-1;i>-1;--i) {
                    let item=entries[i].target,
                        anim=holders.get(item);
                    if(anim!=1){
                        let player=anim && anim.player?anim.player:null;
                        if (entries[i].isIntersecting) {
                            item.style.contentVisibility='';
                            if(player){
                                if(entries[i].intersectionRatio>=.3){
                                    if(player.isPaused){
                                        player.show();
                                        player.play();
                                    }
                                }
                                else if(!player.isPaused){
                                    player.pause();
                                    player.hide();
                                }
                            }else{
                                let obj=new TF_Lottie(item.tfClass('lottie')[0],{
                                    type:'svg',
                                    actions:[{state:'none',tr:'autoplay',path:item.dataset.id}]
                                });
                                holders.set(item,1);
                                obj.run().then(()=>{
                                    if(!obj.player || !TF_LottieBrowse.el){
                                        throw '';
                                    }
                                    item.tfClass('tf_loader')[0].remove();
                                    holders.set(item,obj);
                                    obj.player.loop = true;
                                    obj.player.play();
                                })
                                    .catch(()=>{
                                        holders.delete(item,obj);
                                        obj.destroy(true);
                                        item.remove();
                                    });
                            }
                        }
                        else{
                            item.style.contentVisibility='hidden';
                            if(player && !player.isPaused){
                                player.pause();
                                player.hide();
                            }
                        }
                    }
                }

            }, {
                threshold:[0,.05,.1,.3,.5,.7,.8,.9],
                rootMargin:"0px 0px 150px 0px",
                root:root
            });
        }
    };
    TF_LottieBrowse={
        run(input,labels,nonce){
            this.input=input;
            this.bg=und;
            this.nonce=nonce;
            this.labels=labels;
            return this.show(input.value);
        },
        async render(){
            if(!this.el){
                const _CLICK_=Themify.click,
                    close=doc.createElement('button'),
                    search=doc.createElement('input'),
                    bgWrap=doc.createElement('div'),
                    bg=doc.createElement('div'),
                    menuWrap=doc.createElement('div'),
                    catWrap=doc.createElement('div'),
                    menuIcon=doc.createElement('a'),
                    menu=doc.createElement('ul'),
                    selectedCat=doc.createElement('span'),
                    container=doc.createElement('div'),
                    pagination=doc.createElement('div'),
                    categoryFr=doc.createDocumentFragment(),
                    prms=[this.getJson()];
                this.el=doc.createElement('div');
                this.el.className='lightbox tf_hide';

                catWrap.className='cat_wrap flex';
                menuIcon.href='javascript:;';
                menuIcon.className='menu_icon flex tf_rel';
                menu.className='menu tf_box tf_hidden tf_opacity tf_scrollbar';
                selectedCat.className='selected_cat';
                selectedCat.textContent=this.labels.all;

                search.type='search';
                search.className='search tf_box';
                search.required=true;
                search.setAttribute('inputmode','search');

                close.className='tf_close';

                menuWrap.className='menu_wrap flex tf_rel';
                container.className='container tf_scrollbar';
                container.style.display='none';
                pagination.className='pagination flex';
                initObserver(container);
                let root=doc.tfId('tf_lottie_root');
                if(!root){
                    root=doc.createElement('div');
                    const overlay=doc.createElement('div');

                    root.id='tf_lottie_root';
                    root.style.display='none';

                    overlay.className='overlay tf_abs_t tf_opacity tf_w tf_h tf_opacity tf_hide';

                    overlay.tfOn(_CLICK_,e=>{
                        e.stopPropagation();
                        this.close();
                    },{passive:true});

                    root.attachShadow({
                        mode:'open'
                    }).append( this.el,overlay);

                    doc.body.appendChild(root);
                    prms.push(Themify.loadCss(Themify.url+'css/base.min','tf_base-css',false,this.el));
                    prms.push(Themify.loadCss(Themify.url+'css/admin/lottie-browse','tf_lottie-browse',false,this.el));
                    prms.push(Themify.loadJs('https://cdnjs.cloudflare.com/ajax/libs/lottie-web/5.12.0/lottie.min.js',!!window.lottie,false));
                    prms.push(Themify.loadJs('lottie',!!window.TF_Lottie));
                    prms.push(Themify.fonts(['ti-download','ti-file']));
                    await Promise.all(prms);console.log(document,document.tfId('tf_svg'));
                    this.el.before(document.tfId('tf_svg').cloneNode(true));
                    Themify.loadJs(Themify.url+'js/admin/notification',!!window.TF_Notification);
                }else{
                    root.style.display='none';
                    root.shadowRoot.appendChild(this.el);
                    await Promise.all(prms);
                }
                const cats=Object.keys(jsonData);
                cats.unshift('All');
                for(let i=0;i<cats.length;++i){
                    let li=doc.createElement('li');
                    if(i===0){
                        li.dataset.all=1;
                        li.classList.add('current');
                    }
                    li.textContent=cats[i];
                    categoryFr.appendChild(li);
                }

                bgWrap.className='bg_global bg_wrap tf_box';
                bg.className='bg flex';
                for(let j=0,colors=this.getBg();j<colors.length;++j){
                    let color=doc.createElement('button');
                    color.className=colors[j];
                    color.dataset.bg=j;
                    color.type='button';
                    if(colors[j]==='white'){
                        color.className+=' selected_bg';
                    }
                    bg.appendChild(color);
                }
                menu.appendChild(categoryFr);
                menuIcon.appendChild(selectedCat);
                catWrap.append(menuIcon,menu);
                bgWrap.append(doc.createTextNode(this.labels.bg),bg,search);
                menuWrap.append(catWrap,bgWrap);
                this.el.append(menuWrap,container,pagination,close);
                this.renderItems();
                root.style.display='';

                close.tfOn(_CLICK_,e=>{
                    e.stopPropagation();
                    this.close();
                },{passive:true});

                pagination.tfOn(_CLICK_,e=>{
                    e.stopPropagation();
                    let p=e.target.textContent;
                    if(p && p[0]!=='.'){
                        this.renderItems('',search.value,parseInt(p));
                    }
                },{passive:true});

                bg.tfOn(_CLICK_,e=>{
                    e.stopPropagation();
                    const bg=e.target;
                    if(bg && bg!==e.currentTarget){
                        const colorId=bg.dataset.bg,
                            colors=this.getBg(),
                            selectedColor=this.getBg(colorId);
                        for(let bgs=this.el.tfClass('bg'),i=bgs.length-1;i>-1;--i){
                            for(let childs=bgs[i].children,j=childs.length-1;j>-1;--j){
                                childs[j].classList.toggle('selected_bg',colorId===childs[j].dataset.bg);
                            }
                        }
                        for(let items=this.el.tfClass('lottie'),i=items.length-1;i>-1;--i){
                            let cl=items[i].classList;
                            for(let j=colors.length;j>-1;--j){
                                cl.remove(colors[j]);
                            }
                            cl.add(selectedColor);
                        }
                        this.bg=parseInt(colorId);
                    }
                },{passive:true});

                container.tfOn(_CLICK_,async e=>{
                    e.stopPropagation();
                    const sel=e.target,
                        lottie=sel.closest('.lottie'),
                        color=sel.closest('.bg');
                    if(sel.closest('.dbtn')){
                        const item=sel.closest('.item'),
                            animId=item.dataset.id,
                            data=await TF_Lottie.getJson(animId),
                                title=item.tfClass('title')[0].innerHTML.replaceAll(' ','-'),
                                a = doc.createElement('a'),
                                f = new Blob([JSON.stringify(data)], { type: 'application/json' });
                            a.href = URL.createObjectURL(f);
                            a.download = title+'-'+animId;
                            a.click();
                    }
                    else if(lottie){
                        try{
                            const input=this.input,
                                item=lottie.closest('.item');
                            input.value=await this.confirm(item.dataset.id,item.tfClass('title')[0].innerHTML);
                            await this.close();
                            Themify.triggerEvent(input,'change');

                        }catch (e){

                        }
                    }
                    else if(color && color!==sel){
                        const lottie= color.closest('.item').tfClass('lottie')[0];
                        for(let childs=color.children,i=childs.length-1;i>-1;--i){
                            childs[i].classList.toggle('selected_bg',sel===childs[i]);
                            lottie.classList.remove(this.getBg(childs[i].dataset.bg));
                        }
                        lottie.classList.add(this.getBg(sel.dataset.bg));
                    }
                },{passive:true});

                menu.tfOn(_CLICK_, e=>{
                    e.stopPropagation();
                    const el=e.target,
                        _menu=e.currentTarget;
                    if(el.parentNode===_menu){
                        const current=_menu.tfClass('current')[0],
                            cat=el.textContent;
                        if(current!==el || search.value){
                            search.value='';
                            current.classList.remove('current');
                            el.classList.add('current');
                            this.el.tfClass('selected_cat')[0].textContent=cat;
                            this.renderItems((el.dataset.all?'':cat));
                        }
                    }
                },{passive:true});

                let req,
                    timeout;
                search.tfOn('input',e=>{
                    e.stopPropagation();
                    if(req){
                        cancelAnimationFrame(req);
                    }
                    if(timeout){
                        clearTimeout(timeout);
                    }
                    timeout=setTimeout(()=>{
                        req=requestAnimationFrame(()=>{
                            req=null;
                            const cat=this.el.tfClass('current')[0];
                            this.renderItems((cat.dataset.all?'':cat.textContent),search.value.trim());
                        });
                    },100);
                },{passive:true});
                container.style.display='';
            }
        },
        getBg(bg){
            const colors=[
                'white',
                'grey',
                'black',
                'red',
                'green',
                'blue'
            ];
            return bg!==und?colors[bg]:colors;
        },
        renderItems(catId,search,page) {
            if (!page) {
                page = 1;
            }
            const cores=navigator.hardwareConcurrency,
                limit=Themify.isTouch || !cores || cores<16?32:(cores>=16?60:48),
                offset = (page - 1) * limit,
                container = this.el.tfClass('container')[0],
                pagination = this.el.tfClass('pagination')[0],
                fr = doc.createDocumentFragment(),
                added = new Set(),
                cats = catId?[catId]:Object.keys(jsonData);
            let items = [];
            if(search){
                search=search.toLowerCase();
            }
            container.style['scrollBehavior']='auto';
            container.scrollTop=0;
            container.style['scrollBehavior']='';
            for (let i = 0; i < cats.length; ++i) {
                for (let k in jsonData[cats[i]]) {
                    if(!added.has(k) ){
                        added.add(k);
                        let item=jsonData[cats[i]][k],
                            name=item,
                            bg;
                        if(typeof item!=='string'){
                            name=item.n;
                            bg=item.bg;
                        }
                        if (!search || name.toLowerCase().includes(search)) {
                            let obj={id: k, n: name};
                            if(bg!==und){
                                obj.bg=parseInt(bg);
                            }
                            items.push(obj);
                        }
                    }
                }
            }
            added.clear();
            const foundItems = items.length,
                colors=this.getBg();
            items = items.slice(offset,offset+ limit);
            for (let i = 0; i < items.length; ++i) {
                let item = doc.createElement('div'),
                    loader = doc.createElement('div'),
                    lottie = doc.createElement('div'),
                    bgWrap = doc.createElement('div'),
                    bg = doc.createElement('div'),
                    title = doc.createElement('div'),
                    download=doc.createElement('div'),
                    dwnBtn=doc.createElement('button'),
                    //htmlBtn=doc.createElement('button'),
                    ar=items[i],
                    selectedBg=this.bg!==und?this.bg:ar.bg;
                loader.className = 'tf_loader';
                lottie.className = 'lottie flex tf_w tf_h ';
                lottie.className+=selectedBg!==und?this.getBg(selectedBg):'white';
                bgWrap.className='bg_wrap tf_box tf_w';
                bg.className='bg flex';
                for(let j=0;j<colors.length;++j){
                    let color=doc.createElement('button');
                    color.className=colors[j];
                    color.dataset.bg=j;
                    color.type='button';
                    if(selectedBg===j || (selectedBg===und && colors[j]==='white')){
                        color.className+=' selected_bg';
                    }
                    bg.appendChild(color);
                }
                title.textContent = ar.n;
                title.className = 'title tf_overflow tf_box tf_w tf_h tf_mw';
                if(ar.n.length>26){
                    title.title=ar.n;
                }
                download.className='download flex tf_abs_t';
                dwnBtn.className='dbtn';
                dwnBtn.title='Download';
                dwnBtn.type='button';
                dwnBtn.appendChild(this.getIcon('ti-download'));
                /*
                htmlBtn.title='Get Html';
                htmlBtn.appendChild(this.getIcon('ti-file'));
                htmlBtn.className='hbtn';
                htmlBtn.type='button';
                 */
                item.dataset.id = ar.id;
                item.className = 'item tf_box';
                lottie.appendChild(loader);
                bgWrap.append(doc.createTextNode(this.labels.bg),bg);
                download.appendChild(dwnBtn);
                item.append(lottie,bgWrap, title,download);
                fr.appendChild(item);
                observer.observe(item);
            }
            items=null;
            for(let childs=container.children,i=childs.length-1;i>-1;--i){
                let lottie=holders.get(childs[i]);
                if(lottie){
                    holders.delete(lottie);
                    if(lottie!=1){
                        lottie.destroy(true);
                    }
                }
                observer.unobserve(childs[i]);
                childs[i].remove();
            }
            container.appendChild(fr);
            pagination.replaceChildren(this.getPagination(foundItems,page,limit));
        },
        getPagination(total,page,limit){
            const paginateFr=doc.createDocumentFragment();
            if(total>limit){
                const lastPage = Math.ceil(total / limit),
                    pageLink=number=>{
                        const p=doc.createElement('span');
                        p.textContent=number;
                        if(number===page){
                            p.className='selected_page';
                        }
                        return p;
                    },
                    pageGap= x=> {
                        let res=' ... ';
                        if (x===0) {
                            res= '';
                        }
                        else if (x===1){
                            res= ' ';
                        }
                        else if (x<=10){
                            res=' . ';
                        }
                        else if (x<=100){
                            res=' .. ';
                        }
                        return doc.createTextNode(res);
                    },
                    LINKS_PER_STEP = 3;

                let lastp1 = 1,
                    lastp2 = page,
                    p1 = 1,
                    p2 = page,
                    c1 = LINKS_PER_STEP+1,
                    c2 = LINKS_PER_STEP+1,
                    s1 = doc.createDocumentFragment(),
                    s2 = doc.createDocumentFragment(),
                    step = 1;

                while (true){
                    if (c1>=c2){
                        s1.append(pageGap(p1-lastp1) , pageLink(p1,page));
                        lastp1 = p1;
                        p1 += step;
                        --c1;
                    }
                    else{
                        s2.prepend(pageLink(p2,page),pageGap(lastp2-p2));
                        lastp2 = p2;
                        p2 -= step;
                        --c2;
                    }
                    if (c2===0){
                        step *= 10;
                        p1 += step-1;        // Round UP to nearest multiple of step
                        p1 -= (p1 % step);
                        p2 -= (p2 % step);   // Round DOWN to nearest multiple of step
                        c1 = c2=LINKS_PER_STEP;
                    }
                    if (p1>p2){
                        paginateFr.append(s1,pageGap(lastp2-lastp1),s2);
                        if (lastp2>page||page>=lastPage){
                            break;
                        }
                        lastp1 = page;
                        lastp2 = p2=lastPage;
                        p1 = page+1;
                        c1 = LINKS_PER_STEP;
                        c2 = LINKS_PER_STEP+1;
                        step = 1;
                    }
                }
            }
            return paginateFr;
        },
        destroy(){
            const items=this.el.tfClass('item');
            for(let i=items.length-1;i>-1;--i){
                let anim=holders.get(items[i]);
                if(anim){
                    if(anim!=1) {
                        anim.destroy(true);
                    }
                    holders.delete(anim);
                }
            }
            observer.disconnect();
            this.el.remove();
            holders=new WeakMap();
            if(Themify.isTouch){
                jsonData=null;
            }
            else{
                timer=setTimeout(()=>{
                    jsonData=timer=null;
                },60000);
            }
            observer=this.el=this.input=this.bg=this.labels=this.nonce=null;
        },
        show(selected){
            return new Promise(async resolve=>{
                if(!this.el){
                    await this.render(selected);
                }
                if(timer){
                    clearTimeout(timer);
                    timer=null;
                }
                const overlay=this.el.getRootNode().querySelector('.overlay');
                overlay.classList.remove('tf_hide');
                this.el.classList.remove('tf_hide');
                requestAnimationFrame(()=>{
                    this.el.tfOn('transitionend',resolve,{passive:true,once:true});
                    overlay.classList.remove('tf_opacity');
                    setTimeout(()=>{
                        this.el.classList.add('show');
                    },10);
                });
            });
        },
        confirm(animId,title){
            return new Promise((resolve,reject)=>{
                const wrap=doc.createElement('div'),
                    msg=doc.createElement('div'),
                    btns=doc.createElement('div'),
                    overlay=doc.createElement('div'),
                    close=doc.createElement('button'),
                    dwnBtn=doc.createElement('button'),
                    extBtn=doc.createElement('button'),
                    CLICK=Themify.click,
                    callback=val=>{
                        wrap.remove();
                        overlay.remove();
                        if(!val){
                            reject();
                        }else{
                            resolve(val);
                        }
                    };
                msg.className='msg';
                msg.textContent=this.labels.msg;
                dwnBtn.type=close.type=extBtn.type='button';
                dwnBtn.textContent=this.labels.upload_btn;
                extBtn.textContent=this.labels.external_btn;
                close.className='tf_close';
                btns.className='confirm_buttons flex';

                wrap.tfOn(CLICK,async e=>{
                    e.stopPropagation();
                    let target=e.target,
                        v='';
                    if(target===dwnBtn){
                        v=await this.upload(animId,title);
                    }else if(target===extBtn){
                        v=animId;
                    }
                    callback(v);
                },{passive:true})
                    .className='confirm flex tf_abs_c tf_textc tf_box';

                overlay.tfOn(CLICK,e=>{
                    e.stopPropagation();
                    callback('');
                },{passive:true})
                    .className='overlay confirm_overlay tf_abs_t tf_w tf_h';

                btns.append(dwnBtn,extBtn);
                wrap.append(msg,btns,close);
                this.el.after(wrap,overlay);
            });
        },
        close(){
            return new Promise(resolve=>{
                this.el.getRootNode().querySelector('.overlay')
                    .tfOn('transitionend',function(){
                        this.classList.add('tf_hide');
                        this.classList.remove('tf_opacity');
                    },{passive:true,once:true})
                    .classList.add('tf_opacity');

                this.el.tfOn('transitionend',e=>{
                    e.currentTarget.classList.add('tf_hide');
                    this.destroy();
                    resolve();
                },{passive:true,once:true})
                    .classList.remove('show');
            });
        },
        getIcon(icon, cl) {
            icon = 'tf-' + icon.trim().replace(' ', '-');
            const ns = 'http://www.w3.org/2000/svg',
                use = doc.createElementNS(ns, 'use'),
                svg = doc.createElementNS(ns, 'svg');
            let classes = 'tf_fa ' + icon;
            if (cl) {
                classes += ' ' + cl;
            }
            svg.setAttribute('class', classes);
            use.setAttributeNS(null, 'href', '#' + icon);
            svg.appendChild(use);
            return svg;
        },
        async upload(animId,title){
            await TF_Notification.show('info',this.labels.upload_msg.replaceAll('%title%',title));
            const data=await TF_Lottie.getJson(animId),
                formData = {
                    action:'themify_upload_json',
                    file:animId,
                    title:title || '',
                    nonce:this.nonce,
                    data:JSON.stringify(data)
                };
            let res;
            try {
                res=await Themify.fetch(formData);
                if (!res.success) {
                    throw '';
                }

            }catch (e){
                try{
                    formData.data=new Blob( [ formData.data ], { type: 'text/plain' });
                    res=await Themify.fetch(formData);
                    if (!res.success) {
                        throw res.data;
                    }
                }
                catch(e){
                    await TF_Notification.showHide('error',this.labels.upload_fail.replaceAll('%msg%',e));
                    throw e;
                }
            }
            await TF_Notification.showHide('done','',1000);
            return res.data;
        },
        async getJson(){
            if(!jsonData){
                jsonData=await Themify.fetch('', 'json', {
                    credentials: 'omit',
                    method: 'GET',
                    mode: 'cors'
                }, 'https://themify.org/public-api/lottie/index.json');
            }
        }
    };

})(window.top.document, Themify,undefined);