define(function(require, exports, module) {

	exports.run = function() {

                $("input[type=file]").change(function(){$(this).parents(".uploader").find(".filename").val($(this).val());});
                $("input[type=file]").each(function(){
                if($(this).val()==""){$(this).parents(".uploader").find(".filename").val("");}
                });

	};

});