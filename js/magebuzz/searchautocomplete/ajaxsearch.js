/*
* Copyright (c) 2014 www.magebuzz.com 
*/
var SearchAutocomplete = Class.create();
var searchInput = '';
SearchAutocomplete.prototype = {
	initialize: function(searchUrl,coreSearchUrl, heading,searchInput,noResultText='No results for'){
		this.searchInput = searchInput;
		this.searchUrl = searchUrl;		
		this.coreSearchUrl = coreSearchUrl;		
		this.onSuccess = this.onSuccess.bindAsEventListener(this);        
		this.onFailure = this.onFailure.bindAsEventListener(this);				
		this.currentSearch = ''; 	
		this.heading = heading;
		this.noResultText = noResultText;
    },
	search: function(){	
		var searchBox = $(this.searchInput);
	    
		if(searchBox.value=='')
		{
			return;
		}
		
	    if ((this.currentSearch!="") &&(searchBox.value == this.currentSearch)) {
	        return;
	    }
	    this.currentSearch = searchBox.value;
		
		searchBox.className =  'loading-result input-text';
		var keyword = searchBox.value;
		
		var parameters = {keyword:keyword};
		url = this.searchUrl;//+"keyword/" + escape(keyword);
		 
		new Ajax.Request(url, {
			  method: 'get',
				frequency: 500,
				parameters: parameters,
		    onSuccess: this.onSuccess,
			  onFailure: this.onFailure 
		  });	 
    },
	onFailure: function(transport){
        $(this.searchInput).className ="input-text";
    },
	onSuccess: function(transport)
	{
		var showResults = $('boxResults');
		showResults.style.display = "block";
		var listResults = $('listResults');
		listResults.style.display = "block";
		var searchBox = $(this.searchInput);
		if (transport && transport.responseText) {
			try{
				response = eval('(' + transport.responseText + ')');
			}catch (e) {
				response = {};
			}
			if (response.html != "") {
				this.currentSearch = searchBox.value;
				listResults.update(response.html);
				var heading = '<a href='+this.coreSearchUrl+"?q="+this.currentSearch+'>'+this.heading+'</a>';
				var strHeading = heading.replace("{{keyword}}",this.currentSearch);
				this.updateResultLabel(strHeading);
				searchBox.className = 'search-complete input-text';
      }
			else{
				listResults.update(response.html);
				this.updateResultLabel(this.noResultText +' "<span class="keyword">'+this.currentSearch+'</span>"');
				searchBox.className ="search-complete input-text";
			}			
		}		
	},
	updateResultLabel: function(message)
	{
		$("resultLabel").update(message);
	}
}