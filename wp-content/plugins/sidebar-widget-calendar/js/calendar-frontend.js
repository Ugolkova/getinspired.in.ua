(function($) {
	$(function() {
		
							
		var currentActiveDay = ''; // The current active day, as a date stamp (Y-m-d)
																	
		/**
			*	Calendar DAY events
			*/
			
		var attachDayControls = function() {
			var days = $('.swgc-calendar td.swgc-calendar-bookable');
			
			days.each(function() {
				$(this).click(function() {
					currentActiveDay = $('input', this).attr('value');
					
					var data = {
						action: 'swgc_fetch_available_slots',
						date: $('input', this).attr('value')
					}
					$.post(MyAjax.ajaxurl, data, function(response) {
						$('#swgc-available-slots-async').html(response);
	
					});
					
					var lastActive = $('.swgc-calendar td.swgc-calendar-active');
					lastActive.removeClass('swgc-calendar-active');
					
					$(this).addClass('swgc-calendar-active');
	
				});
				$(this).mouseenter(function() {
					defaultBackgroundColor = $(this).css('background-color');
					$(this).css('background-color', '#efefef');		
				});
				$(this).mouseleave(function() {
					$(this).css('background-color', defaultBackgroundColor);														
				});
			});
		}
		
		attachDayControls();
		
		/**
			*	Calendar CONTROL events
			*/
		
		var attachCalendarControls = function() {
			var calendarControls = $('.swgc-calendar .swgc-calendar-control');
			
			var calendarDirection = {
				swgc_calendar_prev: 'back',
				swgc_calendar_next: 'forward'
			}
		
			calendarControls.each(function() {
				$(this).click(function() {
					
					var data = {
						action: 'swgc_fetch_calendar',
						direction: calendarDirection[$(this).attr('id')],
						currentMonth: $('input[name=swgc_month]').attr('value'),
						currentYear: $('input[name=swgc_year]').attr('value'),
						activeDay: currentActiveDay
					}
					
					$.post(MyAjax.ajaxurl, data, function(response) {
						$('#swgc-calendar-async').html(response);
						
						attachCalendarControls(); // re-attach events
						attachDayControls(); // re-attach events
					});
				});
			});
		}
		attachCalendarControls();
	});
})(jQuery);