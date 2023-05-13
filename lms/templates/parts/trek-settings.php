
<?php global $post; ?>
<!-- make html form that take 'sort' as input field and submit using jQuery.ajax -->
<form id="trek-settings-form">
   <label for="sort"><strong>Sort</strong></label>
   <br />
   <input type="number" name="sort" id="sort" value="<?php echo intval(get_post_meta($post->ID, 'sort', true)); ?>">
   <br />
   <br />
    
   <label for="sort"><strong>Strands</strong></label>
   <br />
   <!-- create check boxes array for 'Matter and Energy Strand', 'Force, Motion, and Energy Strand', 'Earth and Space Strand', 'Organisms and Environments Strand' strands -->
    <?php
        $strands = array(
            'Matter and Energy',
            'Force, Motion, and Energy',
            'Earth and Space',
            'Organisms and Environments'
        );
        foreach($strands as $strand) {
            $checked = in_array($strand, get_post_meta($post->ID, 'strands')) ? 'checked' : '';
            echo '<input type="checkbox" name="' . $strand . '" id="' . $strand . '" ' . $checked . '>';
            echo '<label for="' . $strand . '">' . $strand . '</label>';
            echo '<br />';
        }
    ?>
   <br />
   <br />
   <input type="button" id="save-trek-settings-button" value="Save">
</form>

<!-- JQuery script to submit 'trek-settings-form' using ajax -->
<script type="text/javascript">
   jQuery(document).ready(function($) {
    
      $('#save-trek-settings-button').on("click", function(e) {
        e.preventDefault();
        var sort = $('#sort').val();
        // get strands elements and create array of checked strands
        var strands = [];
        $('input[type=checkbox]').each(function() {
            if($(this).is(':checked')) {
                strands.push($(this).attr('name'));
            }
        });
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'trek_settings',
                sort: sort,
                post_id: <?php echo $post->ID; ?>,
                strands,
            },
            success: function(response) {
                console.log(response);
            }
        });
      });
   });
</script>