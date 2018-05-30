<% if $MoreThanOnePage %>
    <ul class="pagination pull-right">
        <% if $NotFirstPage %>
            <li class="page-item">
              <a class="page-link" href="$PrevLink" aria-label="Previous">
                <span aria-hidden="true">&laquo;</span>
                <span class="sr-only">Previous</span>
              </a>
            </li>
        <% end_if %>

        <% loop $PaginationSummary(3)  %>
            <% if $CurrentBool %>
                <li class="page-item"><a class="page-link">$PageNum</a></li>
            <% else %>
                <% if $Link %>
                    <li class="page-item"><a href="$Link" class="page-link">$PageNum</a></li>
                <% else %>
                    <li><a>...</a></li>
                <% end_if %>
            <% end_if %>
        <% end_loop %>
        
        <% if $NotLastPage %>
            <li class="page-item">
              <a class="page-link" href="$NextLink" aria-label="Next">
                <span aria-hidden="true">&raquo;</span>
                <span class="sr-only">Next</span>
              </a>
            </li>
        <% end_if %>
    </ul>
    
    <div class="clearfix"></div>
<% end_if %>