/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

 define([
    "jquery",
    'Magento_Ui/js/lib/core/class',
], function($,Class){
var RegionUpdater =  Class.extend({
    initialize: function (countryEl, regionTextEl, regionSelectEl, regions, disableAction, clearRegionValueOnDisable)
    {
        this.isRegionRequired = true;
        this.countryEl = document.getElementById(countryEl);
        this.regionTextEl = document.getElementById(regionTextEl);
        this.regionSelectEl = document.getElementById(regionSelectEl);
        this.config = regions['config'];
        delete regions.config;
        this.regions = regions;
        this.disableAction = (typeof disableAction=='undefined') ? 'hide' : disableAction;
        this.clearRegionValueOnDisable = (typeof clearRegionValueOnDisable == 'undefined') ? false : clearRegionValueOnDisable;

        this.countryEl.changeUpdater = this.update.bind(this);

        this.countryEl.addEventListener('change',this.update.bind(this));
    
    },
    disableRegionValidation: function()
    {
        this.isRegionRequired = false;
    },

    update: function()
    {
        if (this.regions[this.countryEl.value]) {
            if (this.lastCountryId!=this.countryEl.value) {
                var i, option, region, def;
                def = this.regionSelectEl.getAttribute('defaultValue');
                if (this.regionTextEl) {
                    if (!def) {
                        def = this.regionTextEl.value.toLowerCase();
                    }
                    this.regionTextEl.value = '';
                }
                this.regionSelectEl.options.length = 1;
                for (regionId in this.regions[this.countryEl.value]) {
                    region = this.regions[this.countryEl.value][regionId];
                    option = document.createElement('OPTION');
                    option.value = regionId;
                    option.text = region.name;
                    option.title = region.name;

                    if (this.regionSelectEl.options.add) {
                        this.regionSelectEl.options.add(option);
                    } else {
                        this.regionSelectEl.appendChild(option);
                    }
                    if (regionId==def || region.name.toLowerCase()==def || region.code.toLowerCase()==def) {
                        this.regionSelectEl.value = regionId;
                    }
                }
            }

            if (this.disableAction=='hide') {
                if (this.regionTextEl) {
                    this.regionTextEl.style.display = 'none';
                    this.regionTextEl.style.disabled = true;
                }
                this.regionSelectEl.style.display = '';
                this.regionSelectEl.disabled = false;
            } else if (this.disableAction=='disable') {
                if (this.regionTextEl) {
                    this.regionTextEl.disabled = true;
                }
                this.regionSelectEl.disabled = false;
            }          
            this.lastCountryId = this.countryEl.value;
        } else {
            if (this.disableAction=='hide') {
                if (this.regionTextEl) {
                    this.regionTextEl.style.display = '';
                    this.regionTextEl.style.disabled = false;
                }
                this.regionSelectEl.style.display = 'none';
                this.regionSelectEl.disabled = true;
            } else if (this.disableAction=='disable') {
                if (this.regionTextEl) {
                    this.regionTextEl.disabled = false;
                }
                this.regionSelectEl.disabled = true;
                if (this.clearRegionValueOnDisable) {
                    this.regionSelectEl.value = '';
                }
            } else if (this.disableAction=='nullify') {
                this.regionSelectEl.options.length = 1;
                this.regionSelectEl.value = '';
                this.regionSelectEl.selectedIndex = 0;
                this.lastCountryId = '';
            }
        }
        
    },

    setMarkDisplay: function(elem, display){
        if(elem.parentNode.parentNode){
            var marks = Element.select(elem.parentNode.parentNode, '.required');
            if(marks[0]){
                display ? marks[0].show() : marks[0].hide();
            }
        }
    }
});
    return RegionUpdater;
});