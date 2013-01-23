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
    load 'trimetbot.rb'
    include TrimetBot
  end

  on :message, /^(!trimet|TrimetBot:)\s*(\d+|help)[,\s]*(\d+)?/ do |m, trash, term, route|
    if term.match(/\d+/)
      m.reply(trimet(term, route))
    else
      m.reply("Usage: !trimet <stopID> for transit tracker results for stopID.")
      m.reply("       !trimet <stopID>, <line> for tracker results for the line at that stopID.")
    end
  end
end

bot.start
