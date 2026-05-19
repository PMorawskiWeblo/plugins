(function (global) {
	'use strict';

	function parseSlotBorder(slot) {
		var border = (slot && slot.border) || {};

		return {
			width: parseInt(border.width, 10) || 0,
			color: border.color || '#ffffff'
		};
	}

	function buildState(slot, maskImg, previewScale) {
		var frame = (slot && slot.frame) || { x: 0, y: 0, width: 200, height: 200 };
		var border = parseSlotBorder(slot);
		var scale = previewScale || 1;

		return {
			mask: maskImg,
			frameW: (frame.width || 100) * scale,
			frameH: (frame.height || 100) * scale,
			borderWidth: border.width,
			borderColor: border.color,
			borderScale: scale
		};
	}

	/**
	 * Paint mask outline ring with padding so top/bottom/left/right are not clipped.
	 *
	 * @param {object} ps Photo/border state.
	 * @return {{canvas: HTMLCanvasElement, pad: number, width: number, height: number}|null}
	 */
	function paint(ps) {
		if (!ps || !ps.mask || !ps.borderWidth || !ps.borderColor) {
			return null;
		}

		var fw = Math.max(1, Math.round(ps.frameW));
		var fh = Math.max(1, Math.round(ps.frameH));
		var scale = ps.borderScale || 1;
		var bw = Math.max(1, Math.round(ps.borderWidth * scale));
		var pad = bw;
		var iw = fw + pad * 2;
		var ih = fh + pad * 2;
		var baseX = pad;
		var baseY = pad;

		var canvas = document.createElement('canvas');
		canvas.width = iw;
		canvas.height = ih;
		var ctx = canvas.getContext('2d');

		for (var ox = -bw; ox <= bw; ox++) {
			for (var oy = -bw; oy <= bw; oy++) {
				if (ox * ox + oy * oy <= bw * bw) {
					ctx.drawImage(ps.mask, baseX + ox, baseY + oy, fw, fh);
				}
			}
		}

		ctx.globalCompositeOperation = 'source-in';
		ctx.fillStyle = ps.borderColor;
		ctx.fillRect(0, 0, iw, ih);

		ctx.globalCompositeOperation = 'destination-out';
		ctx.drawImage(ps.mask, baseX, baseY, fw, fh);
		ctx.globalCompositeOperation = 'source-over';

		return {
			canvas: canvas,
			pad: pad,
			width: iw,
			height: ih
		};
	}

	global.WppMaskBorder = {
		parse: parseSlotBorder,
		buildState: buildState,
		paint: paint
	};
})(typeof window !== 'undefined' ? window : this);
