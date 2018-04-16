<% if $ObjectImage && CanPublicView %>
    <img src="$ObjectImage.PaddedImage(256,256).Watermark.URL" alt="image" class="img-responsive" />
<% else %>
    <% if ObjectDefaultImage %>
        <img src= "$ObjectDefaultImage" alt="" class="img-responsive" />
    <% else %>
        <img src= "$resourceURL(hudhaifas/silverstripe-dataobject-manager: client/images/default-image.jpg)" alt="" class="img-responsive" />
    <% end_if %>

    <div class="caption" style="">
        <h4>$ObjectTitle.LimitCharacters(110)</h4>
    </div>
<% end_if %>