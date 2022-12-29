( function ( wp ) {
    wp.data.dispatch( 'core/notices' ).createNotice(
        'success', // Can be one of: success, info, warning, error.
        RCQ_NOTIFICATION, // Text string to display.
        {
            isDismissible: true, // Whether the user can dismiss the notice.
        }
    );
} )( window.wp );