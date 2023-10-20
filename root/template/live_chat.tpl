<script type="text/javascript">
    //<![CDATA[
    {% if live_chat_data['LiveChatType'] == 'Livetex' %}
        window['liveTex'] = true,
        window['liveTexID'] = '{{ live_chat_data['LiveChatID'] }}',
        window['liveTex_object'] = true;
        {% if live_chat_data['user']  %}
            var LiveTex = {
                onLiveTexReady: function() {
                    LiveTex.setConversationAttributes({'email': '{{ live_chat_data['user']['email']  }}','phone': '{{ live_chat_data['user']['phone']  }}'},
                            {'client_id': '{{ live_chat_data['user']['user_id']  }}'});

                    function onConversationStarted(event) {

                        LiveTex.setVisitorAttributes(
                                function() { console.log('атрибуты сохранены'); },
                                function(error) { console.log('Ошибка: ' + error); },
                                {
                                    name: { 'name': '{{ live_chat_data['user']['name']  }}', 'is_editable': false },
                                    contacts: [{
                                        'value': '{{ live_chat_data['user']['email']  }}',
                                        'type': LiveTex.ContactType.EMAIL
                                    }, {
                                        'value': '{{ live_chat_data['user']['phone']  }}',
                                        'type': LiveTex.ContactType.PHONE
                                    }]
                                }
                        );
                    }

                    function onInvitationWindowShown(event) {

                        LiveTex.setVisitorAttributes(
                                function() { console.log('атрибуты сохранены'); },
                                function(error) { console.log('Ошибка: ' + error); },
                                {
                                    name: { 'name': '{{ live_chat_data['user']['name']  }}', 'is_editable': false },
                                    contacts: [{
                                        'value': '{{ live_chat_data['user']['email']  }}',
                                        'type': LiveTex.ContactType.EMAIL
                                    }, {
                                        'value': '{{ live_chat_data['user']['phone']  }}',
                                        'type': veTex.ContactType.PHONE
                                    }]
                                }
                        );
                    }

                    LiveTex.addEventListener(
                            LiveTex.Event.CONVERSATION_STARTED, onConversationStarted);

                    LiveTex.addEventListener(
                            LiveTex.Event.INVITATION_WINDOW_SHOWN, onInvitationWindowShown);
                }
            };
        {% endif %}

        var chat = (function() {
            var t = document['createElement']('script');
            t.type ='text/javascript';
            t.async = true;
            t.src = '//cs15.livetex.ru/js/client.js';
            var c = document['getElementsByTagName']('script')[0];
            if ( c ) c['parentNode']['insertBefore'](t, c);
            else document['documentElement']['firstChild']['appendChild'](t);
        })();
    {% elseif live_chat_data['LiveChatType'] == 'Jivo' %}
        {% if live_chat_data['user'] %}
            var widget_id = '{{ live_chat_data['LiveChatID'] }}';

            function delete_cookie ( cookie_name )
            {
                var cookie_date = new Date ( );
                cookie_date.setTime ( cookie_date.getTime() - 1 );
                document.cookie = cookie_name + "=;expires=" + cookie_date.toGMTString();
            }
            delete_cookie("jv_client_id_"+widget_id);
            delete_cookie("jv_client_name_"+widget_id);
            delete_cookie("jv_email_"+widget_id);
            delete_cookie("jv_phone_"+widget_id);

            function jivo_onOpen() {
                jivo_api.setContactInfo(
                        {
                            name : '{{ live_chat_data['user']['name']  }}',
                            email : '{{ live_chat_data['user']['email']  }}',
                            phone : '{{ live_chat_data['user']['phone']  }}'
                        }
                );
            }
        {% endif %}
        
        var chat = (function () {
            var widget_id = '{{ live_chat_data['LiveChatID'] }}';
            var s = document.createElement('script');
            s.type = 'text/javascript';
            s.async = true;
            s.src = '//code.jivosite.com/script/widget/' + widget_id;
            var ss = document.getElementsByTagName('script')[0];
            ss.parentNode.insertBefore(s, ss);
        })();
    {% endif %}

    //]]>
</script>