/**
 * Req jQuery plugin
 * -----------------+
 * v 0.3 - 05/01/2014
 *
 * Standardised way of dealing with ajax requests.
 *
 *  - pass a CSRF token to the request (by string or by element)
 *  - handle status codes (403 and 404 provided as examples)
 *  - local success and error callbacks (can be bound on the element or passed in options)
 *  - Switch out the request error handler if needed
 *  - Pass data as a second parameter in form of a jQuery form object, form selector or a JSON object)
 *  - Easy request response helper $.reqResponse(data, callback, context)
 *  - Fallback to a default request response handler if no event listener was bound for req.success or req.error
 */
(function( $ ) {

	/**
	 * A little helper method that loops over responses (if multiple responses were sent).
	 *
	 * $(elemen).on('req.success', function(e, resp, status, jqXHR) {
	 *     $.reqResponse(resp, function(response){
	 *
	 *      // A response always has these properties:
	 *      // response.data JSON|Array
	 *      // response.value string
	 *      // response.type string (info/success/warning/danger)
	 *
	 *     console.log(response);
	 *     }, this);
	 * }
	 *
	 * @param response The response object returned after a request
	 * @param callback Callback to handle individual responses
	 * @param context Optional - Provide a callback context
	 */
	$.reqResponse = function(response, callback, context) {
		if(response.length > 1)
		{
			$.each(response, function(index, resp)
			{
				callback.call(context, resp);
			})
		}
		else
		{
			callback.call(context, response[0]);
		}
	};

	$.fn.req = function( options, form) {
		var El = this;

		//Bind success and error callbacks, if provided
		if(typeof options.success != 'undefined') {
			El.bind('req.success', options.success);
			delete options.success;
		}

		if(typeof options.error != 'undefined') {
			El.bind('req.error', options.error);
			delete options.error;
		}

		//check for defined status code handlers
		var statusCodes = {};

		if(typeof options.statusCodes != 'undefined') {
			statusCodes = options.statusCodes;
			delete options.statusCodes;
		}

		statusCodes = $.extend( {}, $.fn.req.statusCodes, statusCodes );

		// Extend our default options with those provided.
		var opts = $.extend( {}, $.fn.req.defaults, options );

		var csrf = false
		//check for CSRF support
		if(opts.CSRF != false) {

			//if a jQuery element is passed get its content
			if(opts.CSRF instanceof jQuery) {
				csrf = opts.CSRF.text();
			}
			//otherwise pass the provided string
			else {
				csrf = opts.CSRF;
			}
		}

		// if data was defined as JSON object add the CSRF token
		if(opts.data instanceof Object)
		{
			if(csrf != false)
			{
				opts.data[opts.CSRF_key] = csrf;
			}
		}

		//if form is defined let's try to serialize
		if(typeof form != 'undefined') {
			//defined as a jQuery object
			if(form instanceof jQuery) {
				opts.data = form.serialize();

				if(csrf != false)
				{
					opts.data += '&'+opts.CSRF_key+'='+csrf;
				}
			}
			else if(form instanceof Object)
			{
				//it's a JSON object
				opts.data = form;

				if(csrf != false)
				{
					opts.data[opts.CSRF_key] = csrf;
				}
			}
			//defined as a selector
			else {
				opts.data = $(form).serialize();

				if(csrf != false)
				{
					opts.data += '&'+opts.CSRF_key+'='+csrf;
				}
			}
		}

		//handle status codes
		opts.statusCode = {};
		for(code in statusCodes)
		{
			opts.statusCode[code] = statusCodes[code];
		}

		var events = $._data($(El)[0], "events");

		var has_events = {
			'success': false,
			'error': false
		};

		if(typeof events != 'undefined')
		{
			for(i=0;i<events.req.length;i++)
			{
				if(events.req[i].namespace == "error")
				{
					has_events.error = true;
				}
				else if(events.req[i].namespace == "success")
				{
					has_events.success = true;
				}
			}
		}

		// handle successful requests, fallback to default handler if no event listener was set
		opts.success = function(data, status, jqXHR) {

			switch(data.status)
			{
				case 'error':
					if(has_events.error == true)
					{
						El.trigger('req.error', [data.errors, status, jqXHR]);
					}
					else
					{
						$.fn.req.defaultRequestHandlers.error.call(El, data.errors, status, jqXHR);
					}
					break;
				case 'success':
					if(has_events.success == true)
					{
						El.trigger('req.success', [data.response, status, jqXHR]);
					}
					else
					{
						$.fn.req.defaultRequestHandlers.success.call(El, data.response, status, jqXHR);
					}
					break;
			}
		}

		//deal with failing requests
		opts.error = opts.errorHandler;

		//delete anything we don't need passed to $.ajax
		delete opts.handlers;
		delete opts.CSRF;
		delete opts.CSRF_key;

		//finally run the request
		return $.ajax(opts);
	};

	// Plugin defaults
	$.fn.req.defaults = {
		dataType: 'json',
		CSRF: false,
		CSRF_key: 'csrf',
		errorHandler: function (jqXHR, status, error) {
			//status: null, "timeout", "error", "abort", and "parser error"
			//error: textual portion of the HTTP status, such as "Not Found" or "Internal Server Error."
			/**
			 * jqXHR useful methods or property:
			 *  - getResponseHeader()
			 *  - getAllResponseHeaders()
			 *  - status
			 */
			if(typeof $.fn.Req.statusCodes[jqXHR.status] == 'undefined') {
				console.log('The request could not be completed. ('+status+': '+error+')');
				console.log(jqXHR.getAllResponseHeaders());
			}
		}
	};

	// This is the default request response handler that's called if the response isn't handled by an event
	// could be handy to assign functions that spit out notifications when you don't need to do anything
	// with the request data and just use response.value as the value for the notification.
	// overload this object to suit your needs.
	$.fn.req.defaultRequestHandlers = {
		success: function(response, status, jqXHR) {
			console.log(response);
		},
		error: function(response, status, jqXHR) {
			console.log(response)
		}
	};

	// Default handlers for certain statusCodes returned by the request
	// Overload to suit your needs.
	$.fn.req.statusCodes = {
		404: function() {
			alert("request not found");
		},
		403: function() {
			alert("You have no permission to complete this request.");
		}
	};

// End of closure.
})( jQuery );
