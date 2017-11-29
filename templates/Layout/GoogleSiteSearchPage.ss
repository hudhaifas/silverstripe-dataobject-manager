<style>
    .result_list {
        padding: 0;
    }

    .result_list div {
        margin-top: 0;
        margin-bottom: 26px;
    }

    .result_list div h3 {
        display: block;
        overflow: hidden;
        text-overflow: ellipsis;
        -webkit-text-overflow: ellipsis;
        white-space: nowrap;
        font-size: 18px;
        margin: 0;
    }

    .result_list div cite {
        color: #006621;
        font-style: normal;
    }

    .result_list div .sp {
        font-size: 13px;
        line-height: 1.4;
        color: #545454;
        word-wrap: break-word;
    }

</style>

<div id="g_cse" class="container dataobject-page" data-key="$CseKey" data-cx="$CseCx" data-domain="$GoogleDomain">
    <div class="row" style="margin-bottom: 1.5em;">
        <div class="col-md-4">
            $GoogleSiteSearchForm.setTemplate('Form_GoogleSearch')
        </div>
    </div>

    <div class="g_cse_results_header">
    </div>

    <div id="g_cse_results" class="results_loading">
        <ul class="result_refinements"></ul>
        <div class="result_list"></div>

        <div class="result_error">
            <h4>Sorry could not connect to search server. Please try again.</h4>
        </div>

        <div class="result_empty">
            <h4>No results matching that query found. Try another search keyword.</h4>
        </div>

        <div class="result_nosearchterm">
            <h4>Enter a search term to see results.</h4>
        </div>
    </div>
</div>

<!-- formatting for each search result. Scope is set to each result (item). See https://developers.google.com/custom-search/v1/using_rest -->
<script type="text/html" id="result_tmpl">
    <div>
        <h3><a href="{{=link}}">{{=title}}</a></h3>
        <cite class="result_meta">{{=htmlFormattedUrl}}</cite>
        <p class="sp">{{=htmlSnippet}}</p>
    </div>
</script>

<!-- formatting for each refinement -->
<script type="text/html" id="refinement_tmpl">
    <a href="{{=link}}" class="{{=activeClass}}">{{=anchor}}</a>
</script>

<!-- The pre result template is rendered before the result_list if results exist. Scope is the entire response -->
<script type="text/html" id="pre_result_tmpl">
    <sub><%t DataObjectPage.SEARCH_RESULTS_1 'About' %> {{=queries.request[0].totalResults}} <%t DataObjectPage.SEARCH_RESULTS_2 'results' %></sub>
</script>

<!-- The post result template is rendered after the result_list if results exist. Scope is the entire response -->
<script type="text/html" id="post_result_tmpl">
    <ul class="results_pagination pager">
    {{ if(typeof previousLink !== "undefined") }}
    <li class="results_previous"><a href="{{=previousLink}}"><%t DataObjectPage.PREVIOUS 'Previous' %></a></li>
    {{ if(typeof nextLink !== "undefined") }}
    <li class="results_next"><a href="{{=nextLink}}"><%t DataObjectPage.NEXT 'Next' %></a></li> 
    </ul>
</script>
