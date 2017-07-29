jQuery(document).ready(function($) {
    "use strict";

    var endpoints_container = $( ".endpoints-container" );

    /*################################
         SORT AND SAVE ENDPOINTS
     #################################*/

    if( typeof $.fn.nestable != 'undefined' ) {
        endpoints_container.nestable({
            'expandBtnHTML' : '',
            'collapseBtnHTML' : ''
        });
    }

    $( 'form#plugin-fw-wc' ).on( 'submit', function ( ev ) {

        if( typeof $.fn.nestable == 'undefined' ) {
            return;
        }

        var j = $('.dd').nestable('serialize'),
            v = JSON.stringify(j);

        $( 'input.endpoints-order' ).val( v );
    });
    
    /*################################
        OPEN ENDPOINT OPTIONS
    #################################*/

    $(document).on('click', '.open-options', function() {

        var item = $(this).closest('.endpoint');

        $(this).find('i').toggleClass( 'fa-chevron-down' ).toggleClass( 'fa-chevron-up' );

        item.find( '.endpoint-content' ).first().toggleClass('dd-nodrag');
        item.find( '.endpoint-options' ).first().slideToggle();
    });

    /*##############################
        ADD ENDPOINTS
    ###############################*/

    $(document).on('click', '.add_new_field', function(ev){
        ev.stopPropagation();

        var t           = $(this),
            target      = t.data( 'target' ),
            title       = t.html(),
            new_field   = $(document).find( '.new-field-form' ).clone();

        // first init and open dialog
        init_dialog_form( new_field, target, title );

        // then open
        new_field.dialog('open');
    });

    var xhr = false,
        init_dialog_form = function ( content, target, title ) {

            content.dialog({
                title: title,
                modal: true,
                width: 500,
                resizable: false,
                autoOpen: false,
                buttons: [{
                    text: "Save",
                    click: function () {

                        $(this).find('.loader').css( 'display', 'inline-block' );

                        // class aff dield handler
                        $(this).add_new_field_handler( target );

                        $(document).one( 'yith_wcmap_field_added', function() {
                            content.dialog("close");
                        });
                    }
                }],
                close: function (event, ui) {
                    content.dialog("destroy");
                    content.remove();
                }
            });

        };

    $.fn.add_new_field_handler = function( target ){

        var t        = $(this),
            value    = t.find( '#yith-wcmap-new-field' ).val(),
            error    = t.find( '.error-msg' );

        // abort prev ajax request
        if( xhr ) {
            xhr.abort();
        }

        // else check ajax
        xhr = $.ajax({
            url: ywcmap.ajaxurl,
            data: {
                target: target,
                field_name: value,
                action: ywcmap.action_add
            },
            dataType: 'json',
            beforeSend: function(){},
            success: function( res ){

                t.find('.loader').hide();

                // check for error or if field already exists
                if( res.error || endpoints_container.find( '[data-id="' + res.field + '"]').length ) {
                    error.html( res.error );
                    return;
                }

                var new_content = $(res.html);

                $( '.endpoints-container > ol.endpoints > li.endpoint' ).last().after( new_content );

                // reinit select
                applySelect2( new_content.find( 'select' ) );

                var id = new_content.find('textarea').attr('id'),
                    // get tinymce options
                    mceInit = tinyMCEPreInit.mceInit,
                    mceKey  = Object.keys(mceInit)[0],
                    mce     = mceInit[ mceKey ],
                    // get quicktags options
                    qtInit  = tinyMCEPreInit.qtInit,
                    qtKey   = Object.keys(qtInit)[0],
                    qt      = mceInit[ qtKey ];

                // change id
                mce.selector    = id;
                mce.body_class  = mce.body_class.replace( mceKey, id );
                qt.id           = id;

                tinymce.init( mce );
                tinyMCE.execCommand('mceAddEditor', false, id );

                quicktags( qt );
                QTags._buttonsInit();

                $(document).trigger( 'yith_wcmap_field_added' );
            }
        });
    };

    /*##############################
        HIDE / SHOW ENDPOINT
     ##############################*/

    var onoff_field = function( trigger, elem ) {
        var item        = elem.closest('.endpoint'),
            all_check   = item.find( '.hide-show-check' ),
            check       = ( trigger == 'checkbox' ) ? elem : all_check.first(),
            all_link    = item.find( '.hide-show-trigger' ),
            label, checked;

        // set checkbox status
        checked = ( ( check.is(':checked') && trigger == 'checkbox' ) || ( ! check.is(':checked') && trigger == 'link' ) ) ? true : false;
        all_check.prop( 'checked', checked );
        // set label
        label = ( check.is(':checked') ) ? ywcmap.hide_lbl : ywcmap.show_lbl;
        all_link.html( label );
    };

    // event listener
    $(document).on( 'change', '.hide-show-check', function(){
        onoff_field( 'checkbox', $(this) );
    });

    $(document).on( 'click', '.hide-show-trigger', function(){
        onoff_field( 'link', $(this) );
    });

    /*##############################
        REMOVE ENDPOINT
     ##############################*/

    $(document).on('click', '.remove-trigger', function(){
        
        var t = $(this),
            endpoint = t.data('endpoint'),
            to_remove = $( 'input.endpoint-to-remove' );

        if( typeof endpoint == 'undefined' || ! to_remove.length ) {
            return false;
        }

        var r = confirm( ywcmap.remove_alert );
        if ( r == true ) {
            var item = t.closest( '.dd-item' ),
                is_group = item.find( 'ol.endpoints' ),
                val_to_remove = to_remove.val(),
                to_remove_array = val_to_remove.length ? val_to_remove.split(',') : [];

            to_remove_array.push( endpoint );
            // first set value
            to_remove.val( to_remove_array.join(',') );

            // if group move child
            if( is_group.length ) {
                var child_items = is_group.find( 'li.dd-item' );
                // move!
                item.after( child_items );
            }
            // then remove field
            item.remove();
        } else {
            return false;
        }
    });

    /*###########################
        SELECT
    #############################*/

    function format(icon) {
        return '<i class="fa fa-' + icon.text + '"></i> ' + icon.text;
    }
    function applySelect2( select ) {
        if( typeof $.fn.select2 != 'undefined' ) {
            $.each( select, function () {
                $(this).select2({
                    width: "100%",
                    formatResult: format
                });
            });
        }
    }

    applySelect2( endpoints_container.find( 'select' ) );
});