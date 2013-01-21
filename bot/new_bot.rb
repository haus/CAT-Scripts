location = doc.xpath('//xmlns:location').attribute("desc").value
if route
  arrivals = doc.xpath("//xmlns:arrivals[@route=#{route}]")
else
  arrivals = doc.xpath('//xmlns:arrivals')
end

doc.each do | node |
  case node.name
    when "location"
      if (node.depth == 1)
        returnString << "Stop ID: #{stopID} - #{node.attribute("desc")}\n"
      end
    when "arrival"
      count += 1
      if (lineNum == nil or node.attribute("route") == lineNum)
        block = node.attribute("block")
        curTime = Time.now.to_i
        arrival = node.attribute("estimated")
        arrival = Time.at(arrival[0..arrival.length-4].to_i)
        arriveDiff = (arrival.to_i - curTime)
        if (node.attribute("status") == "estimated")
          if (arriveDiff > 3600)
            arriveString = "#{arriveDiff/3600} hour(s), #{(arriveDiff%3600)/60} minutes, #{(arriveDiff%3600)%60} seconds"
          else
            arriveString = "#{arriveDiff/60} minutes, #{arriveDiff%60} seconds"
          end
        else
          arriveString = "<no estimate available>"
        end
        scheduled = node.attribute("scheduled")
        scheduled = Time.at(scheduled[0..scheduled.length-4].to_i)
        results[block] = "#{node.attribute("shortSign")} - Estimated Arrival: #{arriveString} (Scheduled at #{scheduled.strftime("%H:%M")})\n"
      end
  end
end
