<html>
<head>
	<title>Log Reader Status</title>

	<script type="text/javascript" src="assets/js/jquery-1.8.0.min.js"></script>

</head>
<body>

	<p>Downloaded Files: <span id="downloaded-files"></span></p>
	<p>Processed Files: <span id="processed-files"></span></p>
	<p>Log Lines: <span id="log-lines"></span></p>

<script type="text/javascript">

	$(document).ready(function() {
		updateLogLines();
	})

	function updateLogLines() {
		$.ajax({
			url: 'ajax/get-stats.php',
			dataType: 'json',
			success: function(data) {
				$('#log-lines').html(formatNumber(data.rawlogs));
				$('#processed-files').html(formatNumber(data.processed));
				$('#downloaded-files').html(formatNumber(data.downloads));
				updateLogLines();
			}
		})
	}

	function formatNumber(intInput) {
		c = 0; //decimals
		d = "."; //decimal seperator
		t = ","; //thousands seperator
		var n = intInput, c = isNaN(c = Math.abs(c)) ? 2 : c, d = d == undefined ? "," : d, t = t == undefined ? "." : t, s = n < 0 ? "-" : "", i = parseInt(n = Math.abs(+n || 0).toFixed(c)) + "", j = (j = i.length) > 3 ? j % 3 : 0;
        return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
	}

</script>

</body>
</html>