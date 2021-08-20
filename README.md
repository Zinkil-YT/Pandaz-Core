# Pandaz
The Official Core Of The MCPE Practice Server Pandaz.

## Features
- FFA
- Bots Duels
- Duels 
- Party System
- Stats
- Scoreboards
- Leaderboards
- Custom Pots
- Settings
- Ranks & Permissions
- Staff Utilities
- Anti-Cheat
- Database (SqlLite3)

## Config
- Arena Config

```yaml
---
duel-arenas: 
    example-arena:
    
        # The coords where players spawn in a party duel.
        center:
          x: 1
          "y": 1
          z: 1
          
        # The name of the world.
        level: duelworld
        
        # Whether you want to enable player building or not.
        build: true
        
        # The coords where the player spawns in a duel.
        player-pos:
          x: 1
          "y": 1
          z: 1
          
        # The coords where the opponent spawns in a duel.
        opponent-pos:
          x: 1
          "y": 1
          z: 1
          
        # Configure what gamemode this duel map is for.
        # Gamemodes: nodebuff, gapple, fist, sumo, combo
        # Bots: easy, medium, hard, hacker.
        modes:
          - nodebuff
          - easy
...
```

- Leaderboard Config (staticfloatingtexts, updatingfloatingtexts)

```yaml
---
# You can name this whatever you want.
topkills:

    x: 1 #x coord where the floatingtext spawns in.
    y: 1 #y coord where the floatingtext spawns in.
    z: 1 #z coord where the floatingtext spawns in.
    
    # The Title of the floating text.
    title: "Top Kills"
    
    # The bottom part of the floating text.
    
    # Allowed variables: {world}, {ip}, {discord}, {shop}, {vote}, {doubleline}, {line}, {player}, {kills}, {deaths}, {kdr}, {elo}, {coins}, {streak}, {player_health}, {player_max_health}, {online_players}, {online_max_players}, {topkills}, {topdeaths}, {topkdr}, {topelo}, {toplevels}, {topwins}, {toplosses}, {topkillstreaks}, {topdailykills} and {topdailydeaths}
    
    text: "{doubleline}{topkills}"
    
    # The world where the floating text spawns in.
    level: leaderboardworld
    
...
```

<b>Made with ❤ by Zinkil</b>
