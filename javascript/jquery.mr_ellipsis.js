/**
 * NOTE: You can make this work on a fluid width element (i.e. responsively) if you call it on window resize.
 *
 * jquery ellipsis plugin
 * author: Guy Thomas
 * date: 2014-10-16
 * (c) Moodlerooms 2015
 * @returns {$.fn}
 */
$.fn.ellipsis = function() {

    if (!this[0]) {
        return;
    }

    /**
     * Is the last visual character in a string an html entity? If so return the entity else return false
     * @param str
     * @returns {bool | string}
     */
    var lastcharentity = function(str) {
        var re = /&(\#\d+|\#x[A-F0-9]+|[a-zA-Z]+);$/;
        var lastindex = 0;
        while ((match = re.exec(str)) !== null) {
            if (match.index == lastindex) {
                break;
            }
            lastindex = match.index + match[0].length;
            if (lastindex == str.length) {
                return match[0];
            }
        }
        return false;
    };

    /**
     * Get the character entity starting from a specific offset.
     * @param str
     *
     * @returns {bool | string}
     */
    var charentity = function(str, offset) {
        var re = /&(\#\d+|\#x[A-F0-9]+|[a-zA-Z]+);/g;
        var lastindex = 0;
        while ((match = re.exec(str)) !== null) {
            if (match.index == lastindex) {
                break;
            }
            lastindex = match.index + match[0].length;
            if (match.index == offset) {
                return match[0];
            }
        }
        return false;
    };

    /**
     * Shrink string by one character or entity
     *
     * @param str
     * @return string
     */
    var shrinkstring = function(str) {
        // We need to grab the entire length of a html entity if its the last component of the string and
        // use it as a decrementer.
        // otherwise we will get a bug where we trim a portion of a html entity away - e.g. &amp; to &amp and then
        // chrome will just automatically 'fix' the broken entity
        var lastcharentitiy = lastcharentity(str);
        var decrementer = 1;
        if (lastcharentitiy) {
            decrementer = lastcharentitiy.length;
        }
        return (str.substr(0, str.length - decrementer));
    };

    /**
     * Expand a string by one character or entity by comparing it to its original string
     *
     * @param str
     * @param originalstr
     * @return string
     */
    var expandstring = function(str, originalstr) {
        var nextchar = originalstr.substr(str.length, 1);
        if (nextchar == '&') {
            // The next char could be an entity
            var entity = charentity(originalstr, str.length);
            if (entity) {
                // OK, it is an entity so lets add it to str
                return str+entity;
            } else {
                // Looks like its just an unescaped ampersand
                return str + '&';
            }
        }
        return (str + nextchar);
    };

    this.each(function() {

        // Loop counter.
        var l = 0;

        // Log original text.
        if (typeof($(this).data('originaltxt')) == 'undefined') {
            $(this).data('originaltxt', this.innerHTML);
        }

        // Log text line height.
        if (typeof($(this).data('rowheight')) == 'undefined') {
            if ($(this).height() === 0) {
                // I am hidden.
                return;
            }

            // Make characters span two rows and then get row height from half of 2 rows.
            this.innerHTML = '|';
            var scheight = $(this).outerHeight();
            var c=0;
            while ($(this).outerHeight() < scheight*2 && c<1000) {
                c++;
                this.innerHTML += ' |';
            }
            var rowheight = $(this).outerHeight() / 2;

            $(this).data('rowheight', rowheight);
            this.innerHTML = $(this).data('originaltxt');
        }

        rowheight = parseInt($(this).data('rowheight'));

        var maxrows = $(this).data('maxrows');

        // Note - this is in here temporarilly - we need to make it so you can pass max rows into the function.
        maxrows = !maxrows ? 2 : maxrows;

        var maxheight = $(this).data('maxheight');
        if (!maxheight && !maxrows){
            return this;
        } else if (!maxheight && maxrows){
            maxheight = maxrows * rowheight;
        }

        if ($(this).outerHeight() > maxheight) {

            $(this).addClass('ellipsis_toobig');
            // Content is too big, lets shrink it (but never let it go less than 1 row height as that's pointless).
            l = 0;
            while ($(this).outerHeight() > maxheight && $(this).outerHeight() > rowheight) {

                console.log('Comparing '+$(this).outerHeight()+' against maxheight '+maxheight);

                l++;
                if (l > 1000) {
                    console.log('possible infinite loop when shrinking "'+$(this).data('originaltxt')+'"');
                    break;
                }
                this.innerHTML = shrinkstring(this.innerHTML);
                if (this.innerHTML.length < 2) {
                    // we've gone too far here!
                    return;
                }
            }

            if (this.innerHTML.length >= 2) {
                // Trim off one more character, just to be sure.
                this.innerHTML = shrinkstring(this.innerHTML);
            }

        } else {
            // OK, we might need to expand the string if it was previously ellipsed.
            // Note: This is only going to be called if the ellipses is redone on window resize.
            l = 0;
            while ($(this).height() <= maxheight  && this.innerHTML.length < $(this).data('originaltxt').length) {
                l++;
                if (l > 1000) {
                    console.log('possible infinite loop when expanding "'+$(this).data('originaltxt')+'"');
                    break;
                }
                // Make content bigger.
                this.innerHTML = expandstring(this.innerHTML, $(this).data('originaltxt'));
            }
            // Take content back a notch
            if ($(this).height() > maxheight) {
                this.innerHTML = shrinkstring(this.innerHTML);
            } else {
                // Content fits now so get rid of ellipsis_toobig class
                $(this).removeClass('ellipsis_toobig');
            }
        }
    });

    return ({
        shrinkstring: shrinkstring,
        expandstring: expandstring
    });

};

