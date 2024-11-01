jQuery(document).ready(function() {

    //default track donation data load
    jQuery.ajax({
        url: ajax_object.ajaxurl,
        data: {
            action: 'select_track_donation',
            selected_donation_name: ''
        },
        dataType: 'json',
        type: 'POST',
        success: function(data) {

            var data_keys = [];
            var data_values = [];

            for (var key in data) {
                data_keys.push(key);
                data_values.push(data[key]);
            }

            donation_chart_data(data_keys, data_values, selected_donation_name='', selected_donation_year=new Date().getFullYear());
        },
    });

    /* Admin configuration section tabbings section */
    jQuery(".zted_tabs-list li a").click(function(e){
        e.preventDefault();
    });

    jQuery(".zted_tabs-list li").click(function(){
        var tabid = jQuery.trim(jQuery(this).find("a").attr("href"));
        jQuery(".zted_tabs-list li,.zted_tabs div.zted_tab").removeClass("zted_active");   // removing active class from tab

        jQuery(".zted_tab").hide();   // hiding open tab
        jQuery(tabid).show();    // show tab
        jQuery(this).addClass("zted_active"); //  adding active class to clicked tab
        if(tabid == '#zted_track_donation_tab' || tabid == '#zted_support_tab'){
            jQuery('form#zted_admin_layout_form p.submit').hide();
        }else{
            jQuery('form#zted_admin_layout_form p.submit').show();
        }
    });

    jQuery(document).on('click', '#zted_dashboard_tab .zted_donation_switch input[type="checkbox"]', function(){
        if(jQuery(this).prop("checked") == true){
            jQuery(this).val('checked');
            var tab = jQuery.trim(jQuery(this).parent().parent().attr("data-gateway_id"));
            jQuery(this).siblings().next('input[type="hidden"]').val('checked');

            //Check functionality for checkout checkbox availability
            var checked_option = jQuery.trim(jQuery(this).val());
            if(tab == 'zted_donation_on_checkout_inner' && checked_option == 'checked'){
                jQuery(this).parents().find('#zted_donation_on_checkout input[type="checkbox"], #zted_donation_on_checkout input[type="hidden"]').val('unchecked');
                jQuery(this).parents().find('#zted_donation_on_checkout input[type="checkbox"]').removeAttr('checked');

            } else if(tab == 'zted_donation_on_checkout' && checked_option == 'checked'){
                jQuery(this).parents().find('#zted_donation_on_checkout_inner input[type="checkbox"], #zted_donation_on_checkout_inner input[type="hidden"]').val('unchecked');
                jQuery(this).parents().find('#zted_donation_on_checkout_inner input[type="checkbox"]').removeAttr('checked');
            }
        }
        else if(jQuery(this).prop("checked") == false){
            jQuery(this).val('unchecked');
            var tab = jQuery.trim(jQuery(this).parent().parent().attr("data-gateway_id"));
            jQuery(this).siblings().next('input[type="hidden"]').val('unchecked');
        }
    });

    //Email template status
    jQuery(document).on('click', '#zted_email_notification_tab input[type="checkbox"]', function(){
        if(jQuery(this).prop("checked") == true){
            jQuery(this).val('checked');
            jQuery(this).parent().parent().find('#zted_email_status_val').val('checked');
        }
        else if(jQuery(this).prop("checked") == false){
            jQuery(this).val('unchecked');
            jQuery(this).parent().parent().find('#zted_email_status_val').val('unchecked');
        }
    }); 

    //Check donation all input fields value before submit
    jQuery("#publishing-action #publish").click(function (evt) { 

        jQuery('#zted-donation-option-info-metabox .zted_donation_mode input[type="number"]').each(function() {

            var status = jQuery(this).parent().parent().attr('style');
            if(jQuery(this).val() == '' && (status == '' || status == 'display: block;')){
                alert(jQuery(this).attr('data-msg'));
                evt.preventDefault();
                return;
            } 
              
        });
    });

    /* Dropdown section view fields */ 
    var max_fields      = 10;
    var wrapper         = jQuery("#zted_donation_dropdown"); 
    var add_button      = jQuery(".zted_add_price_field"); 
    
    var x = wrapper.find('div.zted_donation_dropdown_pricing').length; 
    jQuery(add_button).click(function(e){ 
        e.preventDefault();

        if(x < max_fields){ 
            x++;
            jQuery('<div class="zted_donation_dropdown_pricing"><span class="zted_add_amount">Add Amount </span><input type="number" name="zted_post_donation[dropdown_price][]" value="" data-msg="Dropdown should not be empty" /><a href="#" class="zted_delete">-</a></div>').insertBefore("#zted_donation_dropdown button.zted_add_price_field"); 
        }else{
            alert('You Reached the limits');
        }
    });
    
    jQuery(wrapper).on("click",".zted_delete", function(e){ 
        e.preventDefault(); 
        if(x>1){
            jQuery(this).parent('div').remove(); x--;
        }else{
            alert('Atleast one amount option is required');
        }
        
    });

    /* Track donation tab data js */
    jQuery(document).on('click', '#zted_track_donation_form a[name="zted_tracking_form_submit"]', function(){
        var selected_donation_name = jQuery.trim(jQuery('#zted_select_track_donation').find(':selected').val());
        var selected_donation_year = jQuery.trim(jQuery('#zted_select_donation_year').find(':selected').val());

        jQuery.ajax({
            url: ajax_object.ajaxurl,
            data: {
                action: 'select_track_donation',
                selected_donation_name: selected_donation_name, selected_donation_year: selected_donation_year
            },
            dataType: 'json',
            type: 'POST',
            success: function(data) {

                var data_keys = [];
                var data_values = [];

                for (var key in data) {
                    data_keys.push(key);
                    data_values.push(data[key]);
                }

                donation_chart_data(data_keys, data_values, selected_donation_name, selected_donation_year);
            },
        });
    });
  
    /* Donation view mode availabilty on click radio button */
    jQuery(document).on('click', '.post-type-donation_option .zted_donation_view_type input[type="radio"]', function(){
        jQuery('.zted_donation_view_type input[type="hidden"]').val('');
        jQuery('div.zted_donation_mode').hide();
        if(jQuery(this).prop("checked") == true){
            jQuery.trim(jQuery(this).parent().find('input[type="hidden"]').val('checked'));
            var curr_donation_type = jQuery.trim(jQuery(this).attr('donation-view'));
            jQuery('#zted_' + curr_donation_type).show();
        }
    });
    /* Display other amount field in dropdown section if checked */
    jQuery(document).on('click', '.post-type-donation_option #zted_other_amt_sec input[type="checkbox"]', function(){
        if(jQuery(this).prop("checked") == true){
            jQuery(this).val('checked');
            jQuery(this).parent().find('.zted_other_amt_field').show();
        } else{
            jQuery(this).val('unchecked');
            jQuery(this).parent().find('.zted_other_amt_field').hide();
        }
    });

    //Validtae post's pricebar radio min-max value
    jQuery(document).on('change', '.post-type-donation_option #zted_donation_pricebar', function(){
        var pricebar_min = parseInt(jQuery(this).find('.zted_min').val());
        var pricebar_max = parseInt(jQuery(this).find('.zted_max').val());

        if (pricebar_max > pricebar_max){
          jQuery(this).find('.zted_max').val(pricebar_max);

        }else if (pricebar_max < pricebar_min){
          jQuery(this).find('.zted_max').val(pricebar_min + 10);
        }
    });
});


//Donation chart data function
function donation_chart_data(data_keys, data_values, selected_donation_name, selected_donation_year){
    var url = window.location.href;
    //checked page URL here
    if (window.location.href.indexOf("zted_track_donation") > -1) {
        var custom_title = (selected_donation_name == '') ? " Donation tracking of ":" donation tracking of ";  
        var ctx = document.getElementById("zted_tracking_data");
        if(window.myChart != undefined)
        window.myChart.destroy();
        window.myChart = new Chart(ctx, {
          type: 'bar',
          data: {
            labels: data_keys,
            datasets: [{
                label: selected_donation_name+custom_title+selected_donation_year,
                data: data_values,
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                borderColor: 'rgba(255,99,132,1)',
                hoverBackgroundColor: 'rgba(255, 99, 132, 0.2)',
                hoverBorderColor: 'rgba(255,99,132,1)',
                borderWidth: 1,
            }]
          },
          options: {
            responsive: false,
            scales: {
                xAxes: [{
                    ticks: {
                      maxRotation: 90,
                      minRotation: 80
                    }
                }],
                yAxes: [{
                    ticks: {
                      beginAtZero: true
                    }
                }]
            }
          }
        }); 
    } //closed condition checked page URL here   
}