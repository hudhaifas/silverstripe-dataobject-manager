<% if $ObjectImage && CanPublicView %>
    <img src="$ObjectImage.Pad(256,256).URL" loading="lazy" class="img-fluid" />
<% else %>
    <% if ObjectDefaultImage %>
        <img src="$ObjectDefaultImage" loading="lazy" class="img-fluid" />
    <% else %>
        <img src="$resourceURL(hudhaifas/silverstripe-dataobject-manager: res/images/default-image.jpg)" loading="lazy" class="img-fluid" />
    <% end_if %>

    <div class="caption" style="">
        <h4>$ObjectTitle.LimitCharacters(110)</h4>
    </div>
<% end_if %>
