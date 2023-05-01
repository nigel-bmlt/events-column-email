// Copyright 2023 nigel
// 
// Licensed under the Apache License, Version 2.0 (the "License");
// you may not use this file except in compliance with the License.
// You may obtain a copy of the License at
// 
//     http://www.apache.org/licenses/LICENSE-2.0
// 
// Unless required by applicable law or agreed to in writing, software
// distributed under the License is distributed on an "AS IS" BASIS,
// WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
// See the License for the specific language governing permissions and
// limitations under the License.

jQuery(document).ready(function ($) {
    $('.ece_email_button').on('click', function (event) {
        $(this).prop('disabled', true);
        const post_id = $(this).attr('data-postid')
        const admin_url = $(this).attr('data-adminurl');
        $.ajax({
            url: admin_url+'?action=ece_post&data='+post_id,
            method: 'POST',
            contentType: 'application/json; charset=utf-8',
            processData: false,
            beforeSend(xhr) {
              xhr.setRequestHeader('X-WP-Nonce', $('#_wpnonce').val());
            },
          })
      });

}
)