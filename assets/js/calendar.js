document.addEventListener('DOMContentLoaded', function () {
	var calendarEl = document.getElementById('lc-calendar');
	if (!calendarEl) {
		return;
	}

	var settings = window.lcEventCalendar || {};
	var restUrl = settings.restUrl || '/wp-json/lc/v1/events';
	var eventColor = settings.eventColor || '#2563EB';
	var workshopColor = settings.workshopColor || '#F97316';

	function hexToRgba(hex, alpha) {
		var parsed = hex.replace('#', '');
		if (parsed.length === 3) {
			parsed = parsed.split('').map(function (c) { return c + c; }).join('');
		}
		var r = parseInt(parsed.substring(0, 2), 16);
		var g = parseInt(parsed.substring(2, 4), 16);
		var b = parseInt(parsed.substring(4, 6), 16);
		return 'rgba(' + r + ', ' + g + ', ' + b + ', ' + alpha + ')';
	}

	var WEEKDAY_LABELS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
	var MONTH_LABELS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

	function formatTooltipDate(date) {
		return WEEKDAY_LABELS[date.getDay()] + ' ' + date.getDate() + ' ' + MONTH_LABELS[date.getMonth()];
	}

	function formatTooltipTime(date) {
		var hours = date.getHours();
		var minutes = date.getMinutes();
		var period = hours >= 12 ? 'PM' : 'AM';
		var displayHours = hours % 12;
		if (displayHours === 0) {
			displayHours = 12;
		}
		var displayMinutes = minutes < 10 ? '0' + minutes : '' + minutes;
		return displayHours + ':' + displayMinutes + period;
	}

	function formatTooltipDateTime(event) {
		if (!event.start) {
			return '';
		}
		var label = formatTooltipDate(event.start) + ', ' + formatTooltipTime(event.start);
		if (event.end) {
			label += ' - ' + formatTooltipTime(event.end);
		}
		return label;
	}

	fetch(restUrl)
		.then(function (response) { return response.json(); })
		.then(function (events) {
			var coloured = events.map(function (e) {
				e.backgroundColor = e.type === 'workshop' ? workshopColor : eventColor;
				e.borderColor = e.backgroundColor;
				return e;
			});

			var calendar = new FullCalendar.Calendar(calendarEl, {
				initialView: 'dayGridMonth',
				firstDay: 1,
				height: 'auto',
				contentHeight: 'auto',
				events: coloured,
				displayEventTime: false,
				eventDisplay: 'block',
				headerToolbar: {
					left: 'prev,next today',
					center: 'title',
					right: 'dayGridMonth,listMonth'
				},
				buttonText: {
					today: 'Today',
					month: 'Calendar',
					list: 'List'
				},
				eventClick: function (info) {
					info.jsEvent.preventDefault();
					if (info.event.url) {
						window.location.href = info.event.url;
					}
				},
				eventDidMount: function (info) {
					var tooltip = null;

					info.el.addEventListener('mouseenter', function () {
						tooltip = document.createElement('div');
						tooltip.className = 'lc-event-tooltip';
						tooltip.style.background = hexToRgba(info.event.backgroundColor || eventColor, 0.85);

						var imageUrl = info.event.extendedProps.image;
						if (imageUrl) {
							var imgEl = document.createElement('img');
							imgEl.src = imageUrl;
							imgEl.alt = '';
							tooltip.appendChild(imgEl);
						}

						var titleEl = document.createElement('strong');
						titleEl.textContent = info.event.title;
						tooltip.appendChild(titleEl);

						var dateTimeLabel = formatTooltipDateTime(info.event);
						if (dateTimeLabel) {
							var dateTimeEl = document.createElement('span');
							dateTimeEl.className = 'lc-tooltip-datetime';
							dateTimeEl.textContent = dateTimeLabel;
							tooltip.appendChild(dateTimeEl);
						}

						var desc = info.event.extendedProps.description;
						if (desc) {
							var descEl = document.createElement('p');
							descEl.textContent = desc;
							tooltip.appendChild(descEl);
						}

						document.body.appendChild(tooltip);

						var rect = info.el.getBoundingClientRect();
						var top = rect.top + window.scrollY - tooltip.offsetHeight - 8;
						var left = rect.left + window.scrollX;

						if (top < window.scrollY + 4) {
							top = rect.bottom + window.scrollY + 8;
						}

						var maxLeft = window.scrollX + document.documentElement.clientWidth - tooltip.offsetWidth - 12;
						if (left > maxLeft) {
							left = maxLeft;
						}
						if (left < window.scrollX + 12) {
							left = window.scrollX + 12;
						}

						tooltip.style.top = top + 'px';
						tooltip.style.left = left + 'px';
					});

					info.el.addEventListener('mouseleave', function () {
						if (tooltip) {
							tooltip.remove();
							tooltip = null;
						}
					});
				}
			});

			calendar.render();
		});
});
