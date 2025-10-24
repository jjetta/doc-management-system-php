<!doctype html>
<html>
<head>
<meta charset="UTF-8">
<title>Document Management Web Front End</title>
<link href="assets/css/bootstrap.css" rel="stylesheet">
<link href="assets/css/bootstrap-fileupload.min.css" rel="stylesheet">
<script src="assets/js/jquery-1.10.2.js"></script>
<script src="assets/js/bootstrap.js"></script>
<script src="assets/js/bootstrap-fileupload.js"></script>
</head>

<body>
    <div class="panel panel-primary">
        <div class="panel-heading">Upload New Load ID</div>
        <div class="panel-body">
            <h3>Please fill out the form below.</h3>
            <hr>
            <form method="post" action="">
                <div class="form-group">
                    <label class="control-label">Loan Number:</label>
                    <input class="form-control" name="loanId" type="text">
                    <span class="help-block"></span>
                </div>
                <div class="form-group">
                    <label class="control-label">Document Type:</label>
                    <select name="docType" class="form-control">
                        <option value="Tax Form">Tax Form</option>
                        <option value="MOU">MOU</option>
                        <option value="Credit">Credit</option>
                        <option value="Personal">Personal</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="control-label">File Upload</label>
                    <div class="">
                        <div class="fileupload fileupload-new" data-provides="fileupload">
                            <div class="fileupload-preview thumbnail" style="width:200px; height:150px">
                            </div>
                            <div class="row">
                                <div class="col-md-2">
                                    <span class="btn btn-file btn-primary">
                                        <span class="fileupload-new">Select File</span>
                                        <span class="fileupload-exists">Change</span>
                                        <input name="userfile" type="file">
                                    </span>
                                </div>
                                <div class="col-md-2">
                                    <a href="#" class="btn btn-danger fileupload-exists" data-dismiss="fileupload">Remove</a>
                            </div>
                        </div>
                    </div>
            </form>
        </div>
    </div>
</body>
</html>
