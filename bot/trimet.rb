require 'cinch'
require 'open-uri'
require 'nokogiri'
require 'cgi'
require 'yaml'

# This bot connects to urban dictionary and returns the first result
# for a given query, replying with the result directly to the sender
config_file = ARGV[0] || File.join(File.dirname(__FILE__), "trimet_config.yaml")
if File.exists?(config_file)
  CONFIG = YAML.load_file(config_file)
  puts CONFIG.inspect
else
  raise "Need a config file"
  exit 1
end

bot = Cinch::Bot.new do
  configure do |c|
    c.server    = CONFIG[:server]
    c.port      = CONFIG[:port]
    c.nick      = CONFIG[:nick]
    c.channels  = CONFIG[:channels]
    c.ssl.use   = CONFIG[:ssl]
  end

  helpers do
    # This method assumes everything will go ok, it's not the best method
    # of doing this *by far* and is simply a helper method to show how it
    # can be done.. it works!
    def trimet(query)
      @apikey = CONFIG[:api_key]
      query = quey.split(",")
      stopID = qury[0].strip
      lineNum = (uery[1] == nil ? nil : query[1].strip)
      count = 0

      returnString = String.new
      url = "http://developer.trimet.org/ws/V1/arrivals?locIDs=#{stopID}&appID=#{@apikey}"
      results = Hash.new

      doc = Nokogiri::XML::Reader(open(url))
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

      if count > 0
        results.each do |key, value|
          returnString << "#{value}"
        end
      else
        returnString << "No results for that query.\n"
      end

      return returnString
    end
  end

  on :message, /^!trimet (.+)/ do |m, term|
    if term == "help"
      m.reply("Usage: !trimet <stopID> for transit tracker results for stopID.")
    else
      m.reply(trimet(term) || "No results found", true)
    end
  end

  on :message, /^(!trimet|Trimet).*(help)/ do |m, target, term|
    if term == "help"
      m.reply("Usage: !trimet <stopID> for transit tracker results for stopID.")
    end
  end

end

bot.start

# injekt> !urban cinch
# MrCinch> injekt: describing an action that's extremely easy.

