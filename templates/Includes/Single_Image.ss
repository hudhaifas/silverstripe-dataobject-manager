<div class="card text-center">
<% with Single %>
    <% if ObjectImage %>
    <a href="$ObjectImage.URL" data-lightbox="dataobject-gallery" data-title="{$Title}">
        <img src="$ObjectImage.Pad(256,256).URL" alt="{$Title}" class="img-fluid" />
    </a>
    <% else %>
    <% if ObjectDefaultImage %>
    <img src= "$ObjectDefaultImage" alt="{$Title}" class="img-fluid" />
    <% else %>
    <img src= "$resourceURL(hudhaifas/silverstripe-dataobject-manager: res/images/default-image.jpg)" alt="{$Title}" class="img-fluid" />
    <% end_if %>

    <div class="caption" style="">
        <h4>$ObjectTitle.LimitCharacters(110)</h4>
    </div>
    <% end_if %>

    <% if canEdit(0) && ObjectEditableImageName %>
    <div class="caption caption-btn" style="">
        <a href="{$Up.UploadLink($ID)}" class="btn-show-upload"><%t DataObjectPage.CHANGE 'Change' %></a>
    </div>
    <% end_if %>
</div>
<% end_with %>