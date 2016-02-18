'use strict';
var path = require('path');
var rePathSepLeftTrim = new RegExp('^' + path.sep + '+');

module.exports = function (pth, strip) {
	if (!strip || strip.length === 0) {
		return pth;
	}

	var pos;

	pth = path.normalize(pth);
	strip = path.normalize(strip);
	pos = pth.indexOf(strip);
	pth = pos === -1 ? pth : pth.slice(pos + strip.length, pth.length);
	pth = pth.replace(rePathSepLeftTrim, '');

	return pth;
};
