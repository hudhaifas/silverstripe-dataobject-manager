<% with Single %>
<div class="card text-center">
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
        <a class="btn-show-form" data-toggle="modal" data-target="#change-image-moda"><%t DataObjectPage.CHANGE 'Change' %></a>
    </div>
    <% end_if %>
</div>
<% end_with %>

<% if $Single.CanEdit && $Single.ObjectEditableImageName %>
    <!-- Modal -->
    <div class="modal fade" id="change-image-moda" tabindex="-1" role="dialog" aria-labelledby="change-image-modal-label" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="change-image-modal-label"><%t DataObjectPage.CHANGE 'Change' %></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    $ImageEditForm($Single.ID)
                </div>
            </div>
        </div>
    </div>
<% end_if %>

<script>
    jQuery(document).ready(function () {
        $('#change-image-moda').on('show.bs.modal', function (e) {
            initFileinput();
        });
    });
</script>
