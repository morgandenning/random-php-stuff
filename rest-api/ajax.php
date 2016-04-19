<!DOCTYPE html>
<html>
	<head>
		<script src="http://code.jquery.com/jquery-1.10.1.min.js"></script>
		<script type="text/javascript">
			$(function() {
				$.ajax({
					"url" : "./index.php/reseller",
					"type" : "GET",
					"dataType" : "json",
					"success" : function(response) {
						$("#response").text(response);
					},
					"error" : function(xhr,textStatus,errorText) {
						$("#response").text(errorText);
					}
				});
			});
		</script>
	</head>
	
	<body>
		<div id="response"></div>
	</body>
</html>