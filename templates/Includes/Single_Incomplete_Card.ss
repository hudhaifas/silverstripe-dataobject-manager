<% with Single %>
    <% if not $IsCompleted %>
        <div class="card">
            <div class="card-head pt-3 px-5">
                <div class="px-4">
                    <% if ObjectImage %>
                        <img src="$ObjectImage.Pad(256,256).URL" loading="lazy" alt="{$Title}" class="card-img-top img-fluid rounded-circle img-thumbnail" />
                    <% else %>
                        <% if ObjectDefaultImage %>
                           <img src="$ObjectDefaultImage" loading="lazy" alt="{$Title}" class="card-img-top img-fluid rounded-circle img-thumbnail" />
                        <% else %>
                            <img src="$resourceURL(hudhaifas/silverstripe-dataobject-manager: res/images/default-image.jpg)" loading="lazy" alt="{$Title}" class="card-img-top img-fluid rounded-circle img-thumbnail" />
                        <% end_if %>
                    <% end_if %>
                </div>
            </div>

            <div class="card-body">
                <h6 class="card-title"><a href="$ShowLink">$ObjectTitle</a></h6>
                <p class="card-text">$Up.ImportantEditForm($ID)</p>
            </div>

            $ProgressBar
        </div>
    <% end_if %>
<% end_with %>
