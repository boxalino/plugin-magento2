(function(factory) {
    window.bxFacets = factory;
} (function() {
    function init() {
        var bxFacets = {},
            lastSelect = '',
            separator = '',
            level = 1,
            currentSelects = {},
            boostParameter = false,
            contextParameterPrefix = '',
            parameterPrefix = 'bx_',
            fieldDependencies = {},
            activeDependencies = {};

        function init(data, useBoostParameter) {
            if(useBoostParameter !== 'undefined' && useBoostParameter === true) {
                boostParameter = true;
            }
            if(data.hasOwnProperty('facets')) {
                bxFacets = data['facets'];
                initFieldDependencies(bxFacets);
            } else {
                throw "No facets provided for Product Finder. Please check the configuration in the intelligence."
            }
            if(data.hasOwnProperty('contextParameterPrefix')) {
                contextParameterPrefix = data['contextParameterPrefix'];
            } else {
                throw "No parameter prefix defined for Product Finder. Please contact us support@boxalino.com for help."
            }
            if(data.hasOwnProperty('parametersPrefix')) {
                parameterPrefix = data['parametersPrefix'];
            }
            if(data.hasOwnProperty('separator')){
                separator = data['separator'];
            }
            if(data.hasOwnProperty('level')){
                level = data['level'];
            }
            checkParams();
        }

        function checkParams(){
            window.location.search.substr(1).split("&").forEach(function(param) {
                param = param.replace('[]', '');
                var contextPrefix = getContextParameterPrefix(),
                    prefix = getParameterPrefix(),
                    facetName = '',
                    value = '';
                if(param.indexOf(contextPrefix) === 0) {
                    if(boostParameter) {
                        value = param.substring(param.lastIndexOf('_') + 1, param.indexOf('='));
                        facetName = param.substring(contextPrefix.length, param.lastIndexOf('_'));
                    } else {
                        value = param.substring(param.lastIndexOf('=') + 1, param.length);
                        facetName = param.substring(contextPrefix.length, param.lastIndexOf('='));
                    }
                    addSelect(facetName, decodeURIComponent(value));
                } else if(param.indexOf(prefix) === 0) {
                    value = decodeURIComponent(param.substring(param.lastIndexOf('=') + 1, param.length)).split(separator);
                    facetName = param.substring(prefix.length, param.lastIndexOf('='));
                    addSelect(facetName, value);
                } else {
                    facetName = 'products_' + param.substring(0, param.indexOf('='));
                    if(bxFacets.hasOwnProperty(facetName)){
                        value = getMappedFacetValue(facetName, decodeURIComponent(param.substring(param.lastIndexOf('=') + 1, param.length)));
                        if(bxFacets.hasOwnProperty(facetName)) {
                            addSelect(facetName, value);
                        }
                    }
                }
            });
        }

        function initFieldDependencies(facets) {
            for(var fieldName in facets) {
                var dependencies = getFacetExtraInfo(fieldName, 'jsonDependencies'),
                    dependencyFieldNames = [];
                if(dependencies !== null) {
                    dependencies.forEach(function(dependency) {
                        dependency['conditions'].forEach(function(condition) {
                            var conditionFieldName = condition['fieldName'];
                            if(dependencyFieldNames.indexOf(conditionFieldName) === -1)
                                dependencyFieldNames.push(conditionFieldName);
                        });

                    });
                }
                fieldDependencies[fieldName] = dependencyFieldNames;
            }
        }

        function getUpdatedValues() {
            var dependencyValues = {};
            activeDependencies = {};
            for(var fieldName in fieldDependencies){
                if(fieldDependencies[fieldName].indexOf(lastSelect) !== -1){
                    activeDependencies[fieldName] = [];
                    dependencyValues[fieldName] = getFacetValues(fieldName)[fieldName];
                    if(activeDependencies[fieldName].length === 0){
                        delete activeDependencies[fieldName];
                    }
                }
            }
            return dependencyValues;
        }

        function getFacetValues(fieldName, exclusion) {
            var values = {},
                fieldNames = (typeof fieldName !== 'undefined' && typeof fieldName === 'string') ? [fieldName] : getFacets(),
                infoKey = typeof exclusion !== 'undefined' && typeof exclusion === 'string' && exclusion !== '' ? exclusion : null;
            fieldNames.forEach(function(fieldName) {
                if(bxFacets.hasOwnProperty(fieldName)) {
                    var facetValues = getCheckedValues(fieldName);
                    if(infoKey) {
                        var t = [];
                        facetValues.forEach(function (val) {
                            if(getFacetValueExtraInfo(fieldName, val, infoKey)) {
                                t.push(val);
                            }
                        });
                        if(t.length) {
                            facetValues = t;
                        }
                    }
                    values[fieldName] = facetValues;
                }
            });
            return values;
        }

        function getCheckedValues(field) {
            var values = bxFacets[field]['facetValues'].slice(),
                dependencies = getFacetExtraInfo(field, 'jsonDependencies');
            if(dependencies !== null) {
                dependencies.forEach(function(dependency) {
                    var conditions = dependency['conditions'],
                        any = dependency['any'],
                        negative = dependency['negative'],
                        conditionResult = negative ? checkConditions(conditions, any) === !negative :
                            checkConditions(conditions, any);
                    if(conditionResult) {
                        if(activeDependencies.hasOwnProperty(field)) {
                            activeDependencies[field].push(dependency);
                        }
                        var effect = dependency['effect'],
                            hide = effect['hide'];
                        values = ((hide == "true") ? hideValues(values, dependency['values']) :
                            (hide == "false" ? dependency['values'] : sortValues(values, dependency['values'], effect['order'])));
                    }
                });
            }
            return values;
        }

        function isFacetValueSelected(field, value){
            var selected = false;
            if(currentSelects.hasOwnProperty(field)) {
                currentSelects[field].forEach(function(select) {
                    if(select == value){
                        selected = true;
                    }
                });
            }
            return selected;
        }

        function sortValues(array, dependencyValues, position) {
            var matchedValues = [];
            dependencyValues.forEach(function(value) {
                var index = array.indexOf(value);
                if(index > -1) {
                    array.splice(index, 1);
                    matchedValues.push(value);
                }
            });
            matchedValues.reverse().forEach(function(value) {
                array.splice(position, 0, value);
            });
            return array;
        }

        function hideValues(array, filterValues) {
            return array.filter(function(el){
                return !checkArrayForMatch(filterValues, el, false);
            });
        }

        function checkConditions(conditions, any) {
            if(conditions.length === 0) return true;
            var conditionResult = false;
            for(var i = 0, l =  conditions.length; i < l; i++) {
                conditionResult = checkCondition(conditions[i]);
                if(any) {
                    if(conditionResult) {
                        break;
                    }
                } else {
                    if(!conditionResult) {
                        break;
                    }
                }
            }
            return conditionResult;
        }

        function checkCondition(condition) {
            var check = false, fieldName = condition['fieldName'];
            if(currentSelects.hasOwnProperty(fieldName)) {
                var select = currentSelects[fieldName],
                    any = condition['any'],
                    fieldValues = condition['fieldValues'],
                    negative = condition['negative'];
                check = negative ? checkArrayForMatch(fieldValues, select, !any) === !negative :
                    checkArrayForMatch(fieldValues, select, !any);
            }
            return check;
        }

        function checkArrayForMatch(array, match, matchAll) {
            var foundMatch = false,
                matchCheck;
            for(var i = 0, len = array.length; i < len; i++) {
                matchCheck = typeof match !== 'string' ?  checkArrayForMatch(match, array[i], false) : array[i] === match;
                if(matchCheck) {
                    foundMatch = true;
                    if(!matchAll) {
                        break;
                    }
                } else {
                    if(matchAll) {
                        foundMatch = false;
                        break;
                    }
                }
            }
            return foundMatch;
        }

        function addSelect(fieldName, selectedValues) {
            selectedValues = Array.isArray(selectedValues) ? selectedValues : [selectedValues];
            if(currentSelects.hasOwnProperty(fieldName)){
                selectedValues.forEach(function(select) {
                    if(currentSelects[fieldName].indexOf(select) === -1) {
                        currentSelects[fieldName].push(select);
                    }
                });
            } else {
                currentSelects[fieldName] = selectedValues;
            }
            lastSelect = fieldName;
        }

        function removeSelect(fieldName, selectedValues) {
            if(currentSelects.hasOwnProperty(fieldName)) {
                if(typeof selectedValues === 'undefined') {
                    currentSelects[fieldName] = [];
                } else {
                    selectedValues = Array.isArray(selectedValues) ? selectedValues : [selectedValues];
                    currentSelects[fieldName] = currentSelects[fieldName].filter(function(select){
                        return !checkArrayForMatch(selectedValues, select, false);
                    });
                }
                if(currentSelects[fieldName].length === 0) {
                    delete currentSelects[fieldName];
                }
            }
        }

        function getFacetLabel(fieldName, language) {
            var label = '', labelInfo = getFacetExtraInfo(fieldName, 'label');
            if(typeof language !== 'undefined' && typeof language === 'string' && labelInfo !== null) {
                labelInfo.forEach(function(info) {
                    if(info.hasOwnProperty('language') && info['language'] === language) {
                        label = info['value'];
                    }
                });
            }
            if(label === '') {
                label = bxFacets[fieldName]['label'];
            }
            return label;
        }

        function getFacetExtraInfo(field, info_key) {
            var info = null,
                extraInfo = bxFacets.hasOwnProperty(field) ? bxFacets[field]['facetExtraInfo'] : {};
            if(extraInfo.hasOwnProperty(info_key)) {
                info = extraInfo[info_key] === '' ? null : extraInfo[info_key];
            }
            return info;
        }

        function getFacets() {
            var fieldNames = [];
            for(var facet in bxFacets) {
                fieldNames.push(facet);
            }
            return fieldNames;
        }

        function getFacetValueIcon(field, value, language){
            var icon = '',
                iconMap = getFacetExtraInfo(field, 'iconMap'),
                iconClass = getFacetExtraInfo(field, 'icon');
            if(iconMap !== null && iconClass !== null){
                iconMap.forEach(function(iconValue) {
                    if(iconValue['value'] === value) {
                        if(typeof language !== 'undefined' && typeof language === 'string') {
                            if(language === iconValue['language'] || iconValue['language'] === '_') {
                                icon = iconClass + " " + iconValue['icon'];
                            }
                        } else {
                            icon =  iconClass + " " + iconValue['icon'];
                        }
                    }
                });
            }
            return icon;
        }

        function getFacetValueExtraInfo(field, value, info_key, filterParam) {
            var info = null;
            if(info_key === 'hidden') {
                if(bxFacets.hasOwnProperty(field)) {
                    if(typeof filterParam !== 'undefined' && typeof filterParam === 'string' && filterParam !== '') {
                        var displaySize = getFacetExtraInfo(field, 'enumDisplayMaxSize');
                        if(displaySize) {
                            displaySize = parseInt(displaySize);
                            var filteredValues = getFacetValues(field ,filterParam)[field];
                            var valuesSize = filteredValues.length;
                            for(var i = 0; i < valuesSize; i++) {
                                var val = filteredValues[i];
                                if(val === value && displaySize < i+1) {
                                    return true;
                                }
                            }
                        }
                    } else {
                        if(bxFacets[field]['hidden_values'] !== undefined) {
                            return bxFacets[field]['hidden_values'].indexOf(value) !== -1;
                        }
                    }
                }
                return false;
            }
            var facetValueExtraInfo = getFacetExtraInfo(field, 'facetValueExtraInfo');
            if(facetValueExtraInfo){
                if(facetValueExtraInfo.hasOwnProperty(value)) {
                    var valueExtraInfo = facetValueExtraInfo[value];
                    if (valueExtraInfo.hasOwnProperty(info_key)) {
                        return valueExtraInfo[info_key];
                    }
                }
            }
            return info;
        }

        function getQuickSearchFacets(){
            var fieldNames =[];
            getFacets().forEach(function(fieldName) {
                if('true' === getFacetExtraInfo(fieldName, 'isQuickSearch')){
                    fieldNames.push(fieldName);
                }
            });
            return fieldNames;
        }

        function getAdditionalFacets(){
            var fieldNames =[];
            getFacets().forEach(function(fieldName) {
                if('true' === getFacetExtraInfo(fieldName, 'isSoftFacet') && null === getFacetExtraInfo(fieldName, 'isQuickSearch')){
                    fieldNames.push(fieldName);
                }
            });
            return fieldNames;
        }

        function getMappedFacetValue(field, value) {
            if(bxFacets[field].hasOwnProperty('facetMapping')) {
                var facetMapping = bxFacets[field]['facetMapping'];
                for(var label in facetMapping) {
                    if(facetMapping[label] == value) {
                        value = label;
                    }
                }
            }
            return value;
        }

        function getFacetParameters() {
            var parameters = [];
            for(var fieldName in currentSelects) {
                if(bxFacets.hasOwnProperty(fieldName)) {
                    if(currentSelects[fieldName].length > 0) {
                        var ignoreFacet = isFacetIgnored(fieldName);
                        var urlParameter = '',
                            paramName = '';
                        if(ignoreFacet || getFacetExtraInfo(fieldName, 'isSoftFacet')) {
                            currentSelects[fieldName].forEach(function(facet_value) {
                                paramName =  contextParameterPrefix + fieldName;
                                if(boostParameter) {
                                    urlParameter = paramName + '_' + encodeURIComponent(facet_value);
                                } else if(currentSelects[fieldName].length == 1){
                                    urlParameter = paramName + '=' + encodeURIComponent(facet_value);
                                } else {
                                    urlParameter = paramName + '[]=' + encodeURIComponent(facet_value);
                                }
                                parameters.push(urlParameter);
                            });
                        }else {
                            var facet_value = currentSelects[fieldName].join(separator);
                            if(bxFacets[fieldName].hasOwnProperty('facetMapping') && bxFacets[fieldName]['facetMapping'].hasOwnProperty(facet_value)) {
                                if(bxFacets[fieldName].hasOwnProperty('parameterName')) {
                                    paramName = bxFacets[fieldName]['paramsName'];
                                } else {
                                    paramName = fieldName.substring(9, fieldName.length);
                                }
                                urlParameter = paramName + "=" + bxFacets[fieldName]['facetMapping'][facet_value];
                            } else {
                                urlParameter = parameterPrefix + fieldName + '=' + encodeURIComponent(facet_value);
                            }
                            parameters.push(urlParameter);
                        }
                    }
                }
            }
            return parameters;
        }

        function getContextParameterPrefix() {
            return contextParameterPrefix;
        }

        function getCurrentSelects(fieldName) {
            var select = null;
            if(typeof fieldName !== 'undefined' && typeof fieldName === 'string') {
                if(currentSelects.hasOwnProperty(fieldName)) {
                    select = currentSelects[fieldName]
                }
            } else {
                select = currentSelects;
            }
            return select;
        }

        function getParameterPrefix() {
            return parameterPrefix;
        }

        function setParameterPrefix(prefix){
            parameterPrefix = prefix;
        }

        function getDataOwnerFacet() {
            var dataOwnerField = null;
            getFacets().forEach(function(fieldName) {
                if(getFacetExtraInfo(fieldName, 'data-owner') !== null) {
                    dataOwnerField = fieldName;
                }
            });
            return dataOwnerField;
        }

        function getCurrentQuestion() {
            var currentQuestionField = null;
            getFacets().forEach(function(fieldName) {
                if(getFacetExtraInfo(fieldName, 'currentQuestion') !== null) {
                    currentQuestionField = fieldName;
                }
            });
            return currentQuestionField;
        }

        function getLevel() {
            return level;
        }

        function isFacetIgnored(fieldName) {
            var ignored = false;
            if(typeof fieldName !== 'undefined' && typeof fieldName === 'string') {
                if(currentSelects.hasOwnProperty(fieldName)){
                    ignored = currentSelects[fieldName].indexOf('*') > -1;
                }
            }
            return ignored;
        }

        return {
            init: init,
            getFacets: getFacets,
            getQuickSearchFacets: getQuickSearchFacets,
            getAdditionalFacets: getAdditionalFacets,
            getFacetValues: getFacetValues,
            isFacetValueSelected: isFacetValueSelected,
            getFacetValueIcon: getFacetValueIcon,
            getFacetValueExtraInfo: getFacetValueExtraInfo,
            getFacetExtraInfo: getFacetExtraInfo,
            getFacetLabel: getFacetLabel,
            getUpdatedValues: getUpdatedValues,
            addSelect: addSelect,
            removeSelect: removeSelect,
            getFacetParameters : getFacetParameters,
            getContextParameterPrefix: getContextParameterPrefix,
            getParameterPrefix : getParameterPrefix,
            setParameterPrefix : setParameterPrefix,
            getCurrentSelects: getCurrentSelects,
            getDataOwnerFacet: getDataOwnerFacet,
            getCurrentQuestion: getCurrentQuestion,
            getLevel: getLevel,
            isFacetIgnored: isFacetIgnored
        };
    }
    return init();
}));


