/**
 * header video module
 */

( (Themify,doc)=> {
    'use strict';
    const _init=videos=>{
        for(let i=videos.length-1;i>-1;--i){
             let videoSrc = videos[i].dataset.fullwidthvideo;
             if (videoSrc && videoSrc.includes('.mp4') && videoSrc.includes(window.location.hostname)) {
                 let wrap=doc.createElement('div'),
                    video=doc.createElement('video');
                    wrap.className='big-video-wrap tf_overflow tf_abs_t tf_w tf_h';
                    video.className='tf_abs_t tf_w tf_h';
                    video.muted=video.autoplay=video.loop=true;
                    video.setAttribute('playsinline','true');
                    video.type='video/mp4';
                    video.src=videoSrc;
                    wrap.appendChild(video);
                    videos[i].prepend(wrap);
             }
        }
    };
    Themify.on('themify_theme_header_video_init',videos=>{
        setTimeout(()=>{
            _init(videos);
        },1500);
    });

})(Themify,document);