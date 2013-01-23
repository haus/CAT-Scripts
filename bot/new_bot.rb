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
    raise error
  end

  results = []
  results << "Stop ID: #{stopID} - #{location}"

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
      scheduled = Time.at(node.attribute("scheduled").value.to_i/1000)
      arrival = "<no estimate available>" if arrival.nil?
    end

    results << "#{node.attribute("shortSign")} - Estimated Arrival: #{arrival} (Scheduled at #{scheduled.strftime("%H:%M")})"
  end

  results.join("\n")
end

def parse_time(time)
  seconds   = time % 60
  minutes   = ((time - seconds) % 3600) / 60
  hours     = (time - seconds) / 3600

  return { :seconds => seconds, :minutes => minutes, :hours => hours }
end
