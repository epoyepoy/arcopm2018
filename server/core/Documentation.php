<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title> ARCOSM API DOCUMENTATION </title>

<link type="text/css" rel="stylesheet" href="styles.css"/>  


</head>

<body>
    
    <div id="page">
        
    <div class="header">ARCOSM API Specification</div>
 
    <table>

    <tr>
        <th>Method</th>
        <th>API</th>
        <th>Request Parameters</th>
        <th>Request Body</th>
        <th> Description </th>
    </tr>
        
    <tr>
        <td><div class="get">GET</div></td>
        <td><div class="url">/api</div></td>
        <td><div class="requestParams">None</div></td>
        <td><div class="requestBody">None</div></td>
        <td><div class="description">Shows the API specification</div></td>
    </tr>
        
    <tr>
        <td><div class="get">GET</div></td>
        <td><div class="url">/surveys</div></td>
        <td><div class="requestParams">None</div></td>
        <td><div class="requestBody">None</div></td>
        <td><div class="description">Returns the list of surveys </div></td>
    </tr>
        
    <tr>
        <td><div class="post">POST</div></td>
        <td><div class="url">/surveys</div></td>
        <td><div class="requestParams">None</div></td>
        <td><div class="requestBody">Survey object</div></td>
        <td><div class="description">Creates a new survey</div></td>
    </tr>
        
    <tr>
        <td><div class="put">PUT</div></td>
        <td><div class="url">/surveys/:id</div></td>
        <td><div class="requestParams">id: The id of the survey to be updated.</div></td>
        <td><div class="requestBody">Survey object</div></td>
        <td><div class="description">Updates the requested survey</div></td>
    </tr>
        
    <tr>
        <td><div class="delete">DELETE</div></td>
        <td><div class="url">/surveys/:id</div></td>
        <td><div class="requestParams">id: The id of the survey to be deleted.</div></td>
        <td><div class="requestBody">Survey object</div></td>
        <td><div class="description">Deletes the requested survey</div></td>
    </tr>


    </table>
        
    </div> <!-- end of page -->    
    
</body>
</html>



