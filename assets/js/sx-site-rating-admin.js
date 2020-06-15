(function($) {
    'use strict'
    $(document).ready(function() {
        var form_updated = false;
        $('#sx-reset-ratings-button').on('click', function(e) {
            var $this = $(this);
            var $messageinput = $("input#reset-message");
            if (form_updated) {
                form_updated = false;
                return;
            }
            e.preventDefault();

            var retVal = confirm(sx_rating_object.text.question);
            if (retVal == true) {
                $.ajax({
                    url: sx_rating_object.ajax_url,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'sx_reset_rating',
                        _wpnonce: sx_rating_object.nonce
                    },
                    success: function(resp) {

                        if (resp.success) {
                            form_updated = true;
                            $messageinput.val(resp.message)
                            $this.trigger('click');
                        } else {
                            form_updated = true;
                            $messageinput.val(resp.message)
                            $this.trigger('click');
                        }
                    },
                    error: function(xhr, ajaxOptions, thrownError) {
                        form_updated = true;
                        $messageinput.val(thrownError + ": " + xhr.status)
                        $this.trigger('click');
                    }
                });
            } else {
                return false;
            }
        })
    });
}(jQuery))
