(function($) {
	$(function() {												
		$('#swgc-error-log').hide(); // Hide the error log initially
		
		var currentActiveDay = ''; // The current active day, as a date stamp (Y-m-d)
		
			
		/**
			*	Mark Unavailable Days
			*/
		
		var markUnavailableMode = false;
		
		$('#swgc-mark-days').click(function() {
			if(markUnavailableMode) {
				markUnavailableMode = false;
				
				$(this).removeClass('swgc-calendar-active');
			}
			else {
				markUnavailableMode = true;
				
				$(this).addClass('swgc-calendar-active');
			}
			
			return false;
		});
																	
		/**
			*	Calendar DAY events
			*/
			
		var attachDayControls = function() {
			var days = $('.swgc-calendar td.swgc-calendar-day');
			
			days.each(function() {
				
				var day = $(this);
				
				$(this).click(function() {
															 									
					if(markUnavailableMode) {
						
						/**
							*	Mark days as unavailable
							*/
							
						var data = {
							action: 'swgc_make_day_unavailable',
							date: $('input', this).attr('value')
						}
						$.post(MyAjax.ajaxurl, data, function(response) {
							if(day.hasClass('swgc-calendar-unavailable')) {
								day.removeClass('swgc-cell-hover');
								day.removeClass('swgc-calendar-unavailable');
								day.addClass('swgc-calendar-bookable');
							}
							else {
								day.removeClass('swgc-cell-hover');
								day.addClass('swgc-calendar-unavailable');
								day.removeClass('swgc-calendar-bookable');
							}
						});
						
					}
					else {
						
						/**
							*	Regular day click event
							*/
							
						currentActiveDay = $('input', this).attr('value');
						
						var data = {
							action: 'swgc_fetch_booked_times',
							date: $('input', this).attr('value')
						}
						$.post(MyAjax.ajaxurl, data, function(response) {
							$('#swgc-bookings-async').html(response);
							
							// Attach booking list events
							attachBookingListControls();
						});
							
						var lastActive = $('.swgc-calendar td.swgc-calendar-active');
						lastActive.removeClass('swgc-calendar-active');
						
						$(this).addClass('swgc-calendar-active');
						
						// Update booking form (date hasn't changed since our previous data object)
						data.action = 'swgc_fetch_booking_form';
						
						$.post(MyAjax.ajaxurl, data, function(response) {
							$('#swgc-form-container').html(response);
							$('#swgc-error-log').hide();
							attachBookingFormSubmitEvent();
						});
					}
				});
				$(this).mouseenter(function() {
					//defaultBackgroundColor = $(this).css('background-color');
					$(this).addClass('swgc-cell-hover');
				});
				$(this).mouseleave(function() {
					$(this).removeClass('swgc-cell-hover');														
				});
			});
			
		}
		
		attachDayControls();
		
		/**
			*	Booking LIST events
			*/
			
		var attachBookingListControls = function() {
			var bookingList = $('#swgc-bookings .swgc-bookings-booking');
			
			bookingList.each(function() {
				$(this).click(function() {
					var data = {
						action: 'swgc_fetch_booking_form',
						id: $('a', this).attr('rel')
					}
					
					$.post(MyAjax.ajaxurl, data, function(response) {
						$('#swgc-form-container').html(response);
						$('#swgc-error-log').hide();
						
						// Make the booking form events
						attachBookingFormSubmitEvent();
						
						// Attach event to delete booking button here
						var deleteBooking = $('#swgc-delete-booking');
						
						deleteBooking.click(function() {
							// Update data action, id hasn't changed
							data.action = 'swgc_delete_booking';
							
							$.post(MyAjax.ajaxurl, data, function(response) {
								// Refresh bookings list
								var data = {
									action: 'swgc_fetch_booked_times',
									date: getLastActiveDay()
								}
								$.post(MyAjax.ajaxurl, data, function(response) {
									$('#swgc-bookings-async').html(response);
									
									// Re-attach controls
									attachBookingListControls();
								});
								
								// Get new / clean booking form
								getNewBookingForm();
								
								// Refresh Calendar
								refreshCalendar();
							});
							
							return false;
						});
					});
				});
			});
		}
		
		attachBookingListControls();
		
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
		
		/**
			*	Booking Form
			*/
		
		var attachBookingFormSubmitEvent = function() {
			$('#swgc-booking-form').submit(function() {
				var inputs = $('#swgc-booking-form :input');
				
				var inputData = {
					action: 'swgc_validate_booking'
				};
				
				inputs.each(function(input) {
					if($(this).attr('type') != 'submit') {
						inputData[$(this).attr('name')] = $(this).val();
					}
				});
				
				$.post(MyAjax.ajaxurl, inputData, function(response) {
					if(response.num_errors > 0) {
						for(var i=0; i<response.num_errors; i++) {
							$('#swgc-error-log').append('<p>' + response.errors[i] + '</p>');
						}
						
						$('#swgc-error-log').slideDown('fast');
					}
					else {				
						// No errors
						// Refresh bookings list
						var data = {
							action: 'swgc_fetch_booked_times',
							date: getLastActiveDay()
						}
						$.post(MyAjax.ajaxurl, data, function(response) {
							$('#swgc-bookings-async').html(response);
							
							// Get new / clean booking form
							getNewBookingForm();
							
							// Refresh Calendar
							refreshCalendar();
						});
					}
				}, 'json');
				
				return false;
			});
		}
		
		attachBookingFormSubmitEvent();
		
		/**
			*	Clear form (Get empty booking form)
			*/
			
		var getNewBookingForm = function() {
			var data = {
				action: 'swgc_fetch_booking_form',
				date: getLastActiveDay()
			}
			
			$.post(MyAjax.ajaxurl, data, function(response) {
				$('#swgc-form-container').html(response);
				$('#swgc-error-log').hide();
				
				attachBookingFormSubmitEvent();
				attachBookingListControls();
			});
		}
		
		/**
			* Refresh Calendar
			*/
			
		var refreshCalendar = function() {
			var data = {
				action: 'swgc_refresh_calendar',
				date: getLastActiveDay()
			}
					
			$.post(MyAjax.ajaxurl, data, function(response) {
				$('#swgc-calendar-async').html(response);
						
				attachCalendarControls(); // re-attach events
				attachDayControls(); // re-attach events
			});
		}
		
		var getLastActiveDay = function() {
			if($('.swgc-calendar td.swgc-calendar-active input').length > 0) {
				// there is an active day
				var date = $('.swgc-calendar td.swgc-calendar-active input').val();
			}
			else {
				// no active day, use the last clicked value
				var date = $('#swgc-booking-form-date').val();
				date = date.split('/');
				date = new Array(date[2], date[1], date[0]).join('-');
			}
			
			return date;
		}
	});
})(jQuery);