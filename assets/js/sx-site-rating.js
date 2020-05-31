(function($) {
  $(document).ready(function() {


    $(".sx-rate-form.rated").on('click', function() {
      $( this ).removeClass('rated');
      $( this ).siblings(".sx-rate-title").removeClass('hide')
    })


    $(".sx-submit-rating").on('click', function() {
      var $this = $(this)
      var val = $this.siblings(".sx-star-wrapper").children("input[name=rating]:checked").val();
      var $errordiv = $this.parent().siblings(".sx-errors")


      if (!val) {
        $errordiv.html(sx_rating_object.text.choose_rate);
        return;

      } else {

        $this.html(sx_rating_object.text.submitting);

        $.ajax({
          url: sx_rating_object.ajax_url,
          type: 'POST',
          dataType: 'json',
          data: {
            action: 'sx_submit_rating',
            _wpnonce: sx_rating_object.nonce,
            rating: val
          },
          success: function(resp) {
            if (resp.success) {
              $(".sx-errors").html("");
              $this.html(sx_rating_object.text.submit);
              $('.sx-rate-form .sx-star-wrapper' ).children('input[value='+val+']').prop('checked', true).trigger("click")

              $(".sx-rate-title").addClass('hide');
              $this.parent().siblings(".sx-rate-title-thanks").addClass('fly').delay(3000).queue(function(){ $(this).removeClass("fly").dequeue();  });
              $(".sx-rate-form").addClass('rated');
              $(".sx-rate-form.rated").on('click', function() {
                 $( this ).removeClass('rated');
                 $( this ).siblings(".sx-rate-title").removeClass('hide')
              })
              //update mo and total number
              $('.sx-mo').each(function(i){
                $(this).contents().first().replaceWith(resp.mo); //only update text
              })

              $('.sx-rating-total strong').html(resp.total_number)

              //update stars
              var star_wrappers=$('.sx-rating-widget-wrapper > div.sx-star-wrapper')
              star_wrappers.each(function(i){
                var star_spans=$(this).children()
                star_spans.html('<i class="dashicons dashicons-star-filled"></i>')
                star_spans.each(function(j){
                  if ( j+1 <= resp.mo ){
                    $(this).children('i').addClass('active');
                  }else if(resp.mo-j>0 && resp.mo-j<1){
                    var $width=(resp.mo-j)*100;
                    var $html= '<i class="dashicons dashicons-star-filled active" style="width:' +$width+ '%;"></i><i class="dashicons dashicons-star-filled inactive"></i></span>'
                    $(this).html($html)
                  }else{
                    $(this).children('i').addClass('inactive');
                  }
                })
              })

            } else {

              $errordiv.html(resp.message);

              $this.html(sx_rating_object.text.submit);

            }
          },
          error: function(xhr, ajaxOptions, thrownError) {
            $errordiv.html(thrownError + ": " + xhr.status);

            $this.html(sx_rating_object.text.submit);

          }

        });

      }

    })

  });
})(jQuery);
