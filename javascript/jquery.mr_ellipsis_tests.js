/**
 * Test shrink and expand functions.
 * @TODO - at some point consider adding to a javascript unit testing framework like QUNIT.
 */
var test_functions = function() {

    var ellipsisplugin = $('*').ellipsis();
    var shrinkstring = ellipsisplugin.shrinkstring;
    var expandstring = ellipsisplugin.expandstring;

    var a = 0;
    var teststrings = [
        "String test one - blah blah blah here we go and hope that this passes &amp;",
        "&amp; small string",
        "Large string with multiple entities &amp; and &nbsp; and &comma;",
        "&comma; start with entity",
        "end with entity &comma;",
        "entity in &comma; middle",
        "entity number &#160; in middle",
        "&#160; entity number at start",
        "entity number at end &#160;",
        "A test with a really really really long name and &amp; an entity &nbsp;"
    ];
    var tests = 0;
    var passes = 0;
    var fails = 0;

    for (var c = 0; c < teststrings.length; c++) {

        var originalstr = teststrings[c];
        console.log('Testing string', originalstr);
        str = originalstr;
        var totallen = str.length;

        // Shrink.
        for (a = 0; a < totallen; a++) {
            str = shrinkstring(str);
            console.log('Shrunk string by ' + a + ' chars', str);
        }

        // Test shrinkage.
        tests++;
        if (str.length < totallen) {
            passes++;
        } else {
            fails++;
        }

        // Expand.
        for (a = 0; a < totallen; a++) {
            str = expandstring(str, originalstr);
            console.log('Expanded string by ' + a + ' chars', str);
        }

        // Test that all strings are now back to normal.
        tests++;
        if (str == originalstr) {
            console.log('Test passed for', originalstr);
            passes++;
        } else {
            console.log('Test failed for', originalstr);
            fails++;
        }
    }
    console.log(passes + ' test passed and ' + fails + ' tests failed');
};

test_functions();