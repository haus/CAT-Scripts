def parse_results(doc, route)
  unless (t_loc = doc.xpath('//xmlns:location')).empty?
    location = t_loc.attribute("desc").value
  end

  if route
    arrivals = doc.xpath("//xmlns:arrival[@route=#{route}]")
  else
    arrivals = doc.xpath('//xmlns:arrival')
  end

  unless (t_error = doc.xpath('//xmlns:errorMessage')).empty?
    error = t_error.inner_text
  end

  arrivals.each do |node|
    puts node.name
    if node.attributes.include?("estimated")
      estimated = node.attribute("estimated").value.to_i/1000
      estimatedDiff = estimated - Time.now.to_i
      estimate = parse_time(estimated)

      if estimate[:hours].zero?
        arrival = "#{estimate[:minutes]} minutes, #{estimate[:seconds]} seconds\n"
      else
        arrival = "#{estimate[:hours]} hour(s), #{estimate[:minutes]} minutes, #{estimate[:seconds]} seconds\n"
      end
    end

    if node.attributes.include?("scheduled")
      scheduled = node.attribute("scheduled").value.to_i/1000
      arrival = "<no estimate available>"
    end

    # results  "Stop ID: #{stopID} - #{node.attribute("desc")}\n"
    # results[block] = "#{node.attribute("shortSign")} - Estimated Arrival: #{arriveString} (Scheduled at #{scheduled.strftime("%H:%M")})\n"
  end
end

def parse_time(time)
  seconds   = time % 60
  minutes   = ((time - seconds) % 3600) / 60
  hours     = (time - seconds) / 3600

  return { :seconds => seconds, :minutes => minutes, :hours => hours }
end
