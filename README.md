miox-domainmapping
==================

Domain mapping for Magento


```xml
<config>
  <global>
    
    ...

    <domainmapping>
        <map1>
            <from>magelab19foo.local</from>
            <to>magelab19.local</to>
        </map1>
        <map2>
            <from>bar.magelab19.local</from>
            <to>magelab19.local</to>
        </map2>
        <map3>
            <from>baz.magelab19.local</from>
            <to>store:french</to>
        </map3>
    </domainmapping>
    
    ...

  </global>
</config>
```
