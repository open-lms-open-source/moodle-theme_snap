/**
 * NOTE: you can make this work on a fluid width element (i.e. responsively) if you call it on window resize.
 * jquery ellipsis plugin
 * author: Guy Thomas
 * date: 2014-10-16
 * (c) Moodle Rooms 2014-10-16
 * @returns {$.fn}
 */
$.fn.ellipsis = function() {

    if (!this[0]){
        return;
    }

    this.each(function(){
        // log original text
        if (typeof($(this).data('originaltxt'))=='undefined') {
            $(this).data('originaltxt', this.innerHTML);
        }

        // log text line height
        if (typeof($(this).data('emheight'))=='undefined') {
            if ($(this).height()==0) {
                // I am hidden
                return;
            }
            this.innerHTML='M';
            // Horrible bodge fix here, I'm adding 2 pixels on because its obviously not calculating the row height
            // correctly, or possibly my logic for working out ellipses is bad.
            $(this).data('emheight', $(this).height()+2);
            this.innerHTML=$(this).data('originaltxt');
        }

        var emheight = parseInt($(this).data('emheight'));

        var maxrows = $(this).data('maxrows');

        // Note - this is in here temporarilly - we need to make it so you can pass max rows into the function.
        maxrows = !maxrows ? 2 : maxrows;

        var maxheight=$(this).data('maxheight');
        if (!maxheight && !maxrows){
            return this;
        } else if (!maxheight && maxrows){
            maxheight = maxrows * emheight;
        }

        //console.log('maxheight: '+maxheight+' maxrows: '+maxrows);
        //console.log('line height = '+emheight);

        if ($(this).height() > maxheight) {
            $(this).addClass('ellipsis_toobig');
            // Content is too big, lets shrink it (but never let it go less than 1 row height as that's pointless).
            while ($(this).height() > maxheight && $(this).height() > emheight) {
                this.innerHTML = this.innerHTML.substr(0, this.innerHTML.length-2);
                if (this.innerHTML.length<2){
                    // we've gone too far here!
                    return;
                }
            }
        } else {
            while ($(this).height() <= maxheight  && this.innerHTML.length<$(this).data('originaltxt').length) {
                //console.log('Making content bigger. My height '+$(this).height()+ ' v maxheight '+maxheight);
                // Make content bigger.
                this.innerHTML = $(this).data('originaltxt').substr(0, this.innerHTML.length+2);
            }
            // Take content back a notch
            if ($(this).height() > maxheight) {
                this.innerHTML = $(this).data('originaltxt').substr(0, this.innerHTML.length-2);
            } else {
                // Content fits now so get rid of ellipsis_toobig class
                $(this).removeClass('ellipsis_toobig');
            }
        }
    });
    return this;
}