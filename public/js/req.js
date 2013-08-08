/**
 * Req jQuery plugin
 * -----------------+
 * v 0.2 - 29/07/2013
 *
 * Standardised way of dealing with ajax requests.
 *
 *  - pass a CSRF token to the request (by string or by element)
 *  - handle status codes (403 and 404 provided as examples)
 *  - local success and error callbacks (can be bound on the element or passed in options)
 *  - Switch out the request error handler if needed
 *  - Pass data as a second parameter in form of a jQuery form object, form selector or a JSON object)
 */
(function( $ ) {

	$.fn.req = function( options, form) {
		var El = this;

		//Bind success and error callbacks, if provided
		if(typeof options.success != 'undefined') {
			El.bind('success', options.success);
			delete options.success;
		}

		if(typeof options.error != 'undefined') {
			El.bind('error', options.error);
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

		//if form is defined let's try to serialize
		if(typeof form != 'undefined') {
			//defined as a jQuery object
			if(form instanceof jQuery) {
				opts.data = form.serialize();
			}
			else if(form instanceof Object)
			{
				//it's a JSON object
				opts.data = form;
			}
			//defined as a selector
			else {
				opts.data = $(form).serialize();
			}
		}

		//handle status codes
		opts.statusCode = {};
		for(code in statusCodes)
		{
			opts.statusCode[code] = statusCodes[code];
		}

		//check for CSRF support
		if(opts.CSRF != false) {
			//if a jQuery element is passed get its content
			if(opts.CSRF instanceof jQuery) {
				opts.data[opts.CSRF_key] = opts.CSRF.text();
			}
			//otherwise pass the provided string
			else {
				opts.data[opts.CSRF_key] = opts.CSRF;
			}
		}


		//handle successful requests
		opts.success = function(data, status, jqXHR) {
			switch(data.status)
			{
				case 'error':
					El.trigger('error', data.errors, status, jqXHR);
					break;
				case 'success':
					El.trigger('success', data.response, status, jqXHR);
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
			 * jqXHR useful methods:
			 *  - getResponseHeader()
			 *  - getAllResponseHeaders()
			 *  - statusCode()
			 */
			if(jqXHR.statusCode() != 403 && jqXHR.statusCode() != 404) {
				console.log('The request could not be completed. ('+status+': '+error+')');
				console.log(jqXHR.getAllResponseHeaders());
			}
		}
	};

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
