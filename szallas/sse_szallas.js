/**
 * js/jquery
 * 
 * @version 2022.08.30.
 * @package SSE szállásfoglaló
 * @author Soós András
 */

function js_forced_ready(callback){
    if (document.readyState != 'loading') { callback(); }
    else if (document.addEventListener) { document.addEventListener('DOMContentLoaded', callback); }
    else
    {
        document.attachEvent('onreadystatechange', function() {
            if (document.readyState == 'complete') { callback(); }
        });
    }
}

$(document).ready(function() {
    js_forced_ready(function() {
        setTimeout(function() {
            events_init();
        }, 250);
    });
});

function events_init()
{
    calendar_clicks();
    activity_clicks();
}

function calendar_clicks()
{
    $(document).on('click', '.day_item', function() {
        if (!$(this).parents('.reservation_block').hasClass('logged_in'))
        {
            $('.day_item').removeClass('clicked');
            $('.day_item').removeClass('selected');
            $(this).addClass('selected');
            $('.ajax_content.ajax_message').css('display','block');
        }
        else
        {
            $(this).removeClass('selected');
            $(this).toggleClass('clicked');
            $('.ajax_content.ajax_message').removeAttr('style');
        }
        
        var day_id = $(this).attr('id');
        if (day_id>0)
        {
            var show_days = show_reservation_days();
            var pad = $('.ajax_content.reservation_datas').data('pad');
            $.ajax({
                url: window.location.href,
                type: 'POST',
                data: 'ajax_event=calendar_day&day_id=' + day_id + '&show_days=' + show_days + ((pad != undefined) ? '&pad=' + JSON.stringify(pad) : ''),
                dataType: 'html',
                success: function (response) {
                    var response_id = $(response).children().attr('class');
                    if (response_id == undefined)
                    {
                        $('.ajax_content.reservation_datas').html('');
                    }
                    else
                    {
                        $('.ajax_content.reservation_datas').html($(response).html());
                    }
                },
                complete: function() {
                    
                }
            });
        }
    });
    
    function show_reservation_days()
    {
        var collect_ids = '';
        $('.day_item.clicked').each(function() {
            collect_ids+= ((collect_ids == '') ? '' : '|') + $(this).attr('id');
        });
        
        return collect_ids;
    }
}

function activity_clicks()
{
    $(document).on('click', '.activity_chkbox > input', function() {
        $('#days_activity_form').children('.submit_btn').removeAttr('title').addClass('active');
        
        var activity_datas = {};
        var json_data = $('#days_activity_datas').val();
        
        if (json_data != null && json_data != undefined && json_data != '')
        {
            activity_datas = $.parseJSON(json_data);
        }
        
        var date_id = $(this).attr('id').substr(0,8);
        var activity = $(this).attr('id').substr(9);
        
        if (!activity_datas[date_id])
        {
            activity_datas[date_id] = {};
        }
        
        if ($(this).prop('checked'))
        {
            activity_datas[date_id][activity] = 1;
        }
        else
        {
            delete activity_datas[date_id][activity];
        }
        
        $('#days_activity_datas').val(JSON.stringify(activity_datas));
    });
}
