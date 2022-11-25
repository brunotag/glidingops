var aRefNew=0;
function padcal(num, size){var s = num+"";while(s.length<size)s="0"+s;return s;}
function whatcol(a,t){for(i=0;i<a.length;i++){if (a[i]==t) return i;}return -1;}
function getXMLNodeVal(node,target)
{
  
  var str="";
  try
  {
    str=node.getElementsByTagName(target)[0].childNodes[0].nodeValue;
  }
  catch(err)
  {
    return str;
  }
  return str;
}

function AddRosterRow(type,name,tableid)
{
 var btable=document.getElementById(tableid);
 var r1=btable.insertRow(-1);
 r1.setAttribute("class","calros");
 var c1 = r1.insertCell(0);
 c1.setAttribute("class","cal6");
 c1.innerHTML=type;
 c1 = r1.insertCell(1);
 c1.setAttribute("class","cal6");
 c1.colSpan=44;
 c1.innerHTML=name;
}

function AddTableRow(row,v,tableid)
{
 var btable=document.getElementById(tableid);
 var r1=btable.insertRow(-1);
 r1.setAttribute("class","cal");
 var c1 = r1.insertCell(0);
 c1.setAttribute("class","cal1");
 c1.innerHTML=v;
 for (i=0;i<44;i++)
 {
   var c=r1.insertCell(i+1);
   c.setAttribute("id","br" + padcal(row,2) + padcal(i,2));
   c.setAttribute("class","cal2");   
 }
}